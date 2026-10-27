<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../services/GrupoService.php';
require_once __DIR__ . '/../../services/EventoAgendaService.php';

$agora = new DateTimeImmutable('now');
$hoje = $agora->setTime(0, 0);
$dataLimite = zubbo_evento_data_limite($agora);

$idUsuario = (int) $_SESSION['usuario']['id'];
$erro = '';
$idGrupoEvento=filter_var($_POST['id_grupo']??$_GET['id_grupo']??null,FILTER_VALIDATE_INT)?:0;
$grupoConvite=null;
if($idGrupoEvento){
    $buscarGrupo=$conn->prepare("SELECT g.id_grupo,g.id_conversa,g.id_criador,g.nome_grupo,
      (SELECT COUNT(*) FROM Participantes_Conversa pc2
       JOIN Usuario u ON u.id_user=pc2.id_user AND u.status_user='ativo'
       WHERE pc2.id_conversa=g.id_conversa) AS participantes
      FROM Grupo g JOIN Participantes_Conversa pc ON pc.id_conversa=g.id_conversa
      WHERE g.id_grupo=? AND pc.id_user=? LIMIT 1");
    $buscarGrupo->execute([$idGrupoEvento,$idUsuario]);
    $grupoConvite=$buscarGrupo->fetch(PDO::FETCH_ASSOC);
    if(!$grupoConvite || !zubbo_grupo_moderador($conn,$grupoConvite,$idUsuario)){
        http_response_code(403);exit('Somente moderadores podem criar eventos para este grupo.');
    }
}


if (empty($_SESSION['csrf_criar_evento'])) {
    $_SESSION['csrf_criar_evento'] = bin2hex(random_bytes(32));
}

try {
    $stmtEsportes = $conn->query("
        SELECT id_esporte, nome_esporte
        FROM Esporte
        ORDER BY nome_esporte
    ");
    $esportes = $stmtEsportes->fetchAll(PDO::FETCH_ASSOC);

    $stmtLocais = $conn->query("
        SELECT id_local, nome_local, endereco_local, tipo_local
        FROM LocalEsp
        WHERE status_local = 'aprovado'
        ORDER BY nome_local
    ");
    $locais = $stmtLocais->fetchAll(PDO::FETCH_ASSOC);
    $nomesTiposLocais = [
        'campo' => 'Campos',
        'quadra' => 'Quadras',
        'ginásio' => 'Ginásios',
        'clube' => 'Clubes',
        'parque' => 'Parques',
    ];
    $locaisPorTipo = array_fill_keys(array_keys($nomesTiposLocais), []);
    foreach ($locais as $localOpcao) {
        $tipoLocal = (string) $localOpcao['tipo_local'];
        if (!isset($locaisPorTipo[$tipoLocal])) {
            $nomesTiposLocais[$tipoLocal] = 'Outros locais';
            $locaisPorTipo[$tipoLocal] = [];
        }
        $locaisPorTipo[$tipoLocal][] = $localOpcao;
    }
    $tipoLocalSelecionado = '';
    foreach ($locais as $localOpcao) {
        if ((string) $localOpcao['id_local'] === (string) ($_POST['id_local'] ?? '')) {
            $tipoLocalSelecionado = (string) $localOpcao['tipo_local'];
            break;
        }
    }
    $modalidadesPermitidas=$conn->query("SELECT le.id_local,le.id_esporte
      FROM Local_Esporte_Permitido le JOIN LocalEsp l ON l.id_local=le.id_local
      WHERE l.status_local='aprovado'")->fetchAll(PDO::FETCH_ASSOC);
    $permitidosPorLocal=[];
    foreach($modalidadesPermitidas as $ml) $permitidosPorLocal[(int)$ml['id_local']][]=(int)$ml['id_esporte'];

} catch (PDOException $e) {
    error_log('Erro ao carregar opções para criar evento: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar o formulário de evento.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    $nomeEvento = trim((string) ($_POST['nome_evento'] ?? ''));
    $dataEvento = (string) ($_POST['data_evento'] ?? '');
    $horarioEvento = (string) ($_POST['horario_evento'] ?? '');
    $idEsporte = filter_var($_POST['id_esporte'] ?? null, FILTER_VALIDATE_INT);
    $idLocal = filter_var($_POST['id_local'] ?? null, FILTER_VALIDATE_INT);
    $duracao = filter_var($_POST['duracao_minutos'] ?? 60, FILTER_VALIDATE_INT);

    if (!is_string($csrf) || !hash_equals($_SESSION['csrf_criar_evento'], $csrf)) {
        http_response_code(403);
        $erro = 'A sessão do formulário expirou. Atualize a página.';
    } elseif (mb_strlen($nomeEvento) < 3 || mb_strlen($nomeEvento) > 100) {
        $erro = 'O nome do evento deve ter entre 3 e 100 caracteres.';
    } elseif (!$idEsporte || !$idLocal || !in_array($duracao, [60, 90, 120], true)) {
        $erro = 'Selecione um esporte e um local válidos.';
    } else {
        $erro = zubbo_evento_validar_agenda($dataEvento, $horarioEvento, $duracao, $agora);
    }

    if ($erro === '') {
        try {
            $conn->beginTransaction();
            if ($grupoConvite) {
                // Serializa com saídas e alterações de moderação antes de criar convites.
                $grupoAtual = zubbo_grupo_bloquear($conn, 'id_grupo', $idGrupoEvento);
                if (!zubbo_grupo_moderador($conn, $grupoAtual, $idUsuario)) {
                    throw new DomainException('Somente moderadores podem criar eventos para este grupo.');
                }
            }
            // Bloqueio por local serializa criações simultâneas nesse poliesportivo.
            $bloquearLocal = $conn->prepare("SELECT id_local FROM LocalEsp WHERE id_local = ? AND status_local = 'aprovado' FOR UPDATE");
            $bloquearLocal->execute([$idLocal]);
            if (!$bloquearLocal->fetchColumn()) {
                throw new RuntimeException('O local não está disponível.');
            }
            $stmt = $conn->prepare('SELECT 1 FROM Esporte WHERE id_esporte = ? LIMIT 1');
            $stmt->execute([$idEsporte]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('O esporte selecionado não existe.');
            }
            // Nunca confiar somente no filtro JavaScript do formulário.
            $verificarLocalModalidade=$conn->prepare("SELECT 1 FROM Local_Esporte_Permitido
                WHERE id_local=? AND id_esporte=? LIMIT 1");
            $verificarLocalModalidade->execute([$idLocal,$idEsporte]);
            if (!$verificarLocalModalidade->fetchColumn()) {
                throw new RuntimeException('Este local não possui confirmação para o esporte selecionado. Escolha outro esporte/local.');
            }
            // Modalidades que exigem confirmação só podem ser usadas em locais
            // cuja aceitação foi registrada pelo ZubboADM.
            $verificarAceite=$conn->prepare("SELECT 1 FROM Esporte e
              WHERE e.id_esporte = ? AND (e.exige_confirmacao_local = 0 OR
                EXISTS (SELECT 1 FROM Local_Esporte_Confirmacao lc
                  WHERE lc.id_esporte = e.id_esporte AND lc.id_local = ?))");
            $verificarAceite->execute([$idEsporte,$idLocal]);
            if (!$verificarAceite->fetchColumn()) {
                throw new RuntimeException('Esta modalidade ainda não foi confirmada pelo administrador para o local escolhido.');
            }

            $stmt = $conn->prepare("
                SELECT 1
                FROM LocalEsp
                WHERE id_local = ? AND status_local = 'aprovado'
                LIMIT 1
            ");
            $stmt->execute([$idLocal]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('O local selecionado não está disponível.');
            }

            // A linha LocalEsp permanece bloqueada até o COMMIT, impedindo
            // criação simultânea de dois eventos no mesmo intervalo do local.
            // Reserva interna do Zubbo; não equivale a reserva oficial da quadra.
            $checar = $conn->prepare("
                SELECT id_evento, nome_evento, data_evento, horario_evento, duracao_minutos
                FROM Evento
                WHERE id_local = ?
                  AND data_evento = ?
                  AND status_evento = 'ativo'
                  AND TIME_TO_SEC(horario_evento) < TIME_TO_SEC(?) + ? * 60
                  AND TIME_TO_SEC(horario_evento) + duracao_minutos * 60 > TIME_TO_SEC(?)
                ORDER BY horario_evento, id_evento
                LIMIT 1
            ");
            $checar->execute([$idLocal, $dataEvento, $horarioEvento, $duracao, $horarioEvento]);
            $conflito = $checar->fetch(PDO::FETCH_ASSOC);
            if ($conflito) {
                $inicioOcupado = substr((string)$conflito['horario_evento'], 0, 5);
                $fimOcupado = (new DateTimeImmutable(
                    $conflito['data_evento'] . ' ' . $conflito['horario_evento']
                ))->modify('+' . (int)$conflito['duracao_minutos'] . ' minutes')->format('H:i');
                throw new RuntimeException(
                    'Não foi possível criar o evento: este local já possui "' .
                    $conflito['nome_evento'] . '" das ' . $inicioOcupado .
                    ' às ' . $fimOcupado . '. Escolha outro horário ou outro local.'
                );
            }

            $stmt = $conn->prepare("
                INSERT INTO Evento (
                    nome_evento,
                    data_evento,
                    horario_evento,
                    duracao_minutos,
                    id_esporte,
                    id_local,
                    id_criador
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nomeEvento,
                $dataEvento,
                $horarioEvento,
                $duracao,
                $idEsporte,
                $idLocal,
                $idUsuario,
            ]);

            $novoEvento = (int) $conn->lastInsertId();
            if($grupoConvite){
                $conn->prepare("INSERT INTO Grupo_Evento(id_grupo,id_evento) VALUES(?,?)")
                    ->execute([(int)$grupoConvite['id_grupo'],$novoEvento]);
                $avisos=$conn->prepare("INSERT INTO Notificacao
                  (id_destinatario,id_remetente,id_conversa,id_mensagem,id_evento,tipo)
                  SELECT pc.id_user,?,NULL,NULL,?,'convite_evento'
                  FROM Participantes_Conversa pc
                  JOIN Usuario u ON u.id_user=pc.id_user AND u.status_user='ativo'
                  WHERE pc.id_conversa=? AND pc.id_user<>?");
                $avisos->execute([$idUsuario,$novoEvento,(int)$grupoConvite['id_conversa'],$idUsuario]);
            }
            $conn->commit();
            $_SESSION['csrf_criar_evento'] = bin2hex(random_bytes(32));

            header('Location: detalhes-evento.php?id_evento=' . $novoEvento, true, 303);
            exit;
        } catch (DomainException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            http_response_code(403);
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log('Erro ao criar evento: ' . $e->getMessage());
            $erro = 'Não foi possível criar o evento. Tente novamente.';
        } catch (RuntimeException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $erro = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/head.php';

function escaparCriarEvento($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<section class="evento-criar-container">
    <h1><?= $grupoConvite ? 'Criar evento do grupo' : 'Criar evento' ?></h1>
    <?php if ($grupoConvite): ?>
    <p>Grupo: <strong><?= htmlspecialchars($grupoConvite['nome_grupo'],ENT_QUOTES,'UTF-8') ?></strong> ·
       <?= (int)$grupoConvite['participantes'] ?> participantes.
       Ao salvar, todos os outros participantes receberão um convite. Ninguém será inscrito sem confirmar.</p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p class="evento-erro" role="alert"><?= escaparCriarEvento($erro) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= escaparCriarEvento($_SESSION['csrf_criar_evento']) ?>">
        <?php if($grupoConvite): ?>
            <input type="hidden" name="id_grupo" value="<?= (int)$grupoConvite['id_grupo'] ?>">
        <?php endif; ?>

        <div>
            <label for="nome_evento">Nome do evento</label>
            <input
                type="text"
                name="nome_evento"
                id="nome_evento"
                minlength="3"
                maxlength="100"
                value="<?= escaparCriarEvento($_POST['nome_evento'] ?? '') ?>"
                required
            >
        </div>

        <div>
            <label for="tipo_local_evento">Tipo de local</label>
            <select id="tipo_local_evento" required>
                <option value="">Selecione um tipo de local</option>
                <?php foreach ($nomesTiposLocais as $tipo => $nomeTipo): ?>
                    <option value="<?= escaparCriarEvento($tipo) ?>" <?= $tipoLocalSelecionado === $tipo ? 'selected' : '' ?>><?= escaparCriarEvento($nomeTipo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="campo-local-evento">
            <label for="id_local">Local</label>
            <select name="id_local" id="id_local" required>
                <option value="">Escolha primeiro o tipo de local</option>
                <?php foreach ($locaisPorTipo as $tipo => $locaisDoTipo): ?>
                    <optgroup label="<?= escaparCriarEvento($nomesTiposLocais[$tipo]) ?>">
                        <?php foreach ($locaisDoTipo as $local): ?>
                            <option
                                value="<?= (int) $local['id_local'] ?>"
                                data-tipo="<?= escaparCriarEvento($tipo) ?>"
                                <?= (string) ($_POST['id_local'] ?? '') === (string) $local['id_local'] ? 'selected' : '' ?>
                            ><?= escaparCriarEvento($local['nome_local']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <a id="ver-local-mapa" class="evento-ver-local-mapa" href="#" target="_blank" rel="noopener noreferrer" hidden>Ver local no mapa ↗</a>
            <p class="evento-ajuda">Confirme a reserva do espaço com o responsável.</p>
        </div>

        <div>
            <label for="data_evento">Data</label>
            <input
                type="date"
                name="data_evento"
                id="data_evento"
                min="<?= $hoje->format('Y-m-d') ?>"
                max="<?= $dataLimite->format('Y-m-d') ?>"
                aria-describedby="ajuda-data-evento"
                value="<?= escaparCriarEvento($_POST['data_evento'] ?? '') ?>"
                required
            >
            <p id="ajuda-data-evento" class="evento-ajuda">Até <?= $dataLimite->format('d/m/Y') ?>.</p>
        </div>

        <div>
            <label for="horario_evento">Horário</label>
            <select name="horario_evento" id="horario_evento" aria-describedby="ajuda-horario-evento" required>
                <option value="">Escolha a data e o local</option>
                <?php foreach (zubbo_evento_horarios() as $horario): ?>
                    <option value="<?= $horario ?>" <?= ($_POST['horario_evento'] ?? '') === $horario ? 'selected' : '' ?>><?= $horario ?></option>
                <?php endforeach; ?>
            </select>
            <p id="ajuda-horario-evento" class="evento-ajuda" aria-live="polite">Escolha a data e o local para ver os horários livres.</p>
        </div>

        <div>
            <label for="id_esporte">Esporte</label>
            <select name="id_esporte" id="id_esporte" required>
                <option value="">Selecione um esporte</option>
                <?php foreach ($esportes as $esporte): ?>
                    <option
                        value="<?= (int) $esporte['id_esporte'] ?>"
                        <?= (string) ($_POST['id_esporte'] ?? '') === (string) $esporte['id_esporte'] ? 'selected' : '' ?>
                    >
                        <?= escaparCriarEvento($esporte['nome_esporte']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p id="aviso-modalidade" class="evento-ajuda" aria-live="polite">Escolha o local para ver os esportes.</p>
        </div>

        <div>
            <label for="duracao_minutos">Duração estimada</label>
            <select id="duracao_minutos" name="duracao_minutos" required>
                <?php foreach ([60 => '1 hora', 90 => '1 hora e 30 minutos', 120 => '2 horas'] as $minutos => $texto): ?>
                    <option value="<?= $minutos ?>" <?= (int)($_POST['duracao_minutos'] ?? 60) === $minutos ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Criar evento</button>
    </form>
</section>

<script>
(() => {
 const esportesPorLocal=<?= json_encode($permitidosPorLocal,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
 const tipoLocal=document.getElementById('tipo_local_evento');
 const local=document.getElementById('id_local');
 const campoLocal=document.getElementById('campo-local-evento');
 const verLocalMapa=document.getElementById('ver-local-mapa');
 const urlMapa=<?= json_encode(zubbo_url('/app/views/painel/Painel-inicial.php'), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
 const esporte=document.getElementById('id_esporte');
 const aviso=document.getElementById('aviso-modalidade');
 const data=document.getElementById('data_evento');
 const ajudaData=document.getElementById('ajuda-data-evento');
 const duracao=document.getElementById('duracao_minutos');
 const horario=document.getElementById('horario_evento');
 const ajudaHorario=document.getElementById('ajuda-horario-evento');
 const horarioInicial=<?= json_encode((string)($_POST['horario_evento'] ?? ''), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
 const urlHorarios=<?= json_encode(zubbo_url('/app/views/eventos/horarios-disponiveis.php'), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
 let requisicao;
 if(!tipoLocal||!local||!campoLocal||!verLocalMapa||!esporte) return;
 function atualizarLinkMapa() {
   verLocalMapa.hidden=!local.value;
   verLocalMapa.href=local.value ? urlMapa+'?id_local='+encodeURIComponent(local.value) : '#';
 }
 const opcoesLocais=Array.from(local.querySelectorAll('option[data-tipo]'));
 function mostrarLocaisDoTipo(preservarSelecao=false) {
   const selecionado=preservarSelecao ? local.value : '';
   const opcoes=[new Option(tipoLocal.value ? 'Selecione um local' : 'Escolha primeiro o tipo de local', '')];
   for(const opcao of opcoesLocais) {
     if(opcao.dataset.tipo===tipoLocal.value) {
       const copia=opcao.cloneNode(true);
       copia.removeAttribute('selected');
       opcoes.push(copia);
     }
   }
   local.replaceChildren(...opcoes);
   if(selecionado) local.value=selecionado;
   if(!local.value) local.selectedIndex=0;
   campoLocal.hidden=!tipoLocal.value;
   atualizarLinkMapa();
 }
 function atualizarLimiteData() {
   // Usa o mesmo fuso do servidor, mesmo se o visitante estiver em outra região.
   const partes=new Intl.DateTimeFormat('en-US', {
     timeZone:'America/Sao_Paulo',year:'numeric',month:'2-digit',day:'2-digit'
   }).formatToParts(new Date());
   const valores=Object.fromEntries(partes.map(parte=>[parte.type,parte.value]));
   const hoje=`${valores.year}-${valores.month}-${valores.day}`;
   if(data.min===hoje) return false;
   const ano=Number(valores.year);
   const mes=Number(valores.month);
   const ultimoDiaMesSeguinte=new Date(Date.UTC(ano,mes+1,0)).getUTCDate();
   const limite=new Date(Date.UTC(ano,mes,Math.min(Number(valores.day),ultimoDiaMesSeguinte)));
   const maximo=limite.toISOString().slice(0,10);
   data.min=hoje;
   data.max=maximo;
   ajudaData.textContent=`Até ${maximo.slice(8,10)}/${maximo.slice(5,7)}/${maximo.slice(0,4)}.`;
   if(data.value && (data.value<hoje||data.value>maximo)) {
     data.value='';
   }
   atualizarHorarios();
   return true;
 }
 function limparHorarios(mensagem) {
   horario.replaceChildren(new Option(mensagem, ''));
   ajudaHorario.textContent=mensagem;
 }
 async function atualizarHorarios() {
   requisicao?.abort();
   if(!local.value||!data.value) {
     limparHorarios('Escolha a data e o local para ver os horários livres.');
     return;
   }
   const controller=new AbortController();
   requisicao=controller;
   const escolhido=horario.value||horarioInicial;
   limparHorarios('Consultando horários livres...');
   const parametros=new URLSearchParams({id_local:local.value,data_evento:data.value,duracao_minutos:duracao.value});
   try {
     const resposta=await fetch(urlHorarios+'?'+parametros,{signal:controller.signal,cache:'no-store'});
     if(!resposta.ok) throw new Error('Falha na consulta');
     const resultado=await resposta.json();
     if(controller!==requisicao) return;
     horario.replaceChildren(new Option('Selecione um horário', ''));
     for(const valor of resultado.horarios) horario.add(new Option(valor,valor));
     if(resultado.horarios.includes(escolhido)) horario.value=escolhido;
     ajudaHorario.textContent=resultado.horarios.length
       ? 'Horários livres das 05:30 às 22:30, conforme a duração escolhida.'
       : 'Não há horários livres para esta data, local e duração.';
   } catch(erro) {
     if(erro.name==='AbortError') return;
     limparHorarios('Não foi possível consultar os horários. Tente novamente.');
   }
 }
 function atualizar() {
   const permitidos=(esportesPorLocal[local.value]||[]).map(Number);
   for(const opt of esporte.options) {
     if(!opt.value) continue;
     opt.disabled=!permitidos.includes(Number(opt.value));
     opt.hidden=opt.disabled;
   }
   if(esporte.selectedOptions[0]?.disabled) esporte.value='';
   aviso.textContent=local.value
     ? (permitidos.length ? 'Esportes disponíveis no local selecionado.'
       : 'Sem esportes confirmados. Consulte o administrador.')
     : 'Escolha o local para ver os esportes.';
 }
 local.addEventListener('change',()=>{atualizarLinkMapa();atualizar();atualizarHorarios();});
 tipoLocal.addEventListener('change',()=>{
   mostrarLocaisDoTipo();
   atualizar();
   atualizarHorarios();
 });
 data.addEventListener('change',atualizarHorarios);
 duracao.addEventListener('change',atualizarHorarios);
 document.addEventListener('visibilitychange',()=>{if(!document.hidden) atualizarLimiteData();});
 window.addEventListener('focus',atualizarLimiteData);
 setInterval(atualizarLimiteData,60*1000);
 mostrarLocaisDoTipo(true);
 atualizar();
 if(!atualizarLimiteData()) atualizarHorarios();
})();
</script>
<?php
include __DIR__ . '/../includes/under-bar.php';
include __DIR__ . '/../includes/footer.php';
?>
