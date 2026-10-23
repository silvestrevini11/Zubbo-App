<?php
require_once __DIR__ . '/../../middleware/auth.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$erro = '';
$idGrupoEvento=filter_var($_POST['id_grupo']??$_GET['id_grupo']??null,FILTER_VALIDATE_INT)?:0;
$grupoConvite=null;
if($idGrupoEvento){
    $buscarGrupo=$conn->prepare("SELECT g.id_grupo,g.id_conversa,g.nome_grupo,
      (SELECT COUNT(*) FROM Participantes_Conversa pc2
       JOIN Usuario u ON u.id_user=pc2.id_user AND u.status_user='ativo'
       WHERE pc2.id_conversa=g.id_conversa) AS participantes
      FROM Grupo g JOIN Participantes_Conversa pc ON pc.id_conversa=g.id_conversa
      WHERE g.id_grupo=? AND pc.id_user=? LIMIT 1");
    $buscarGrupo->execute([$idGrupoEvento,$idUsuario]);
    $grupoConvite=$buscarGrupo->fetch(PDO::FETCH_ASSOC);
    if(!$grupoConvite){http_response_code(403);exit('Você não participa desse grupo.');}
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
        SELECT id_local, nome_local, endereco_local
        FROM LocalEsp
        WHERE status_local = 'aprovado'
        ORDER BY nome_local
    ");
    $locais = $stmtLocais->fetchAll(PDO::FETCH_ASSOC);
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
        $dataHora = DateTime::createFromFormat('Y-m-d H:i', $dataEvento . ' ' . $horarioEvento);
        $errosData = DateTime::getLastErrors();

        if (
            !$dataHora
            || ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
            || $dataHora <= new DateTime('now')
            || ((int)substr($horarioEvento,0,2)*60+(int)substr($horarioEvento,3,2)+$duracao > 1440)
        ) {
            $erro = 'Escolha uma data e um horário futuros.';
        }
    }

    if ($erro === '') {
        try {
            $conn->beginTransaction();
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
            <label for="data_evento">Data</label>
            <input
                type="date"
                name="data_evento"
                id="data_evento"
                min="<?= date('Y-m-d') ?>"
                value="<?= escaparCriarEvento($_POST['data_evento'] ?? '') ?>"
                required
            >
        </div>

        <div>
            <label for="horario_evento">Horário</label>
            <input
                type="time"
                name="horario_evento"
                id="horario_evento"
                value="<?= escaparCriarEvento($_POST['horario_evento'] ?? '') ?>"
                required
            >
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
            <p id="aviso-modalidade" aria-live="polite">Selecione o local para ver os esportes disponíveis.</p>
        </div>

        <div>
            <label for="id_local">Local</label>
            <select name="id_local" id="id_local" required>
                <option value="">Selecione um local</option>
                <?php foreach ($locais as $local): ?>
                    <option
                        value="<?= (int) $local['id_local'] ?>"
                        <?= (string) ($_POST['id_local'] ?? '') === (string) $local['id_local'] ? 'selected' : '' ?>
                    >
                        <?= escaparCriarEvento($local['nome_local']) ?> — <?= escaparCriarEvento($local['endereco_local']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
 const local=document.getElementById('id_local');
 const esporte=document.getElementById('id_esporte');
 const aviso=document.getElementById('aviso-modalidade');
 if(!local||!esporte) return;
 function atualizar() {
   const permitidos=(esportesPorLocal[local.value]||[]).map(Number);
   for(const opt of esporte.options) {
     if(!opt.value) continue;
     opt.disabled=!permitidos.includes(Number(opt.value));
     opt.hidden=opt.disabled;
   }
   if(esporte.selectedOptions[0]?.disabled) esporte.value='';
   aviso.textContent=local.value
     ? (permitidos.length ? 'Escolha uma das modalidades disponíveis neste local.'
       : 'Nenhuma modalidade confirmada para este local. Peça ao administrador para validar.')
     : 'Selecione o local para ver os esportes disponíveis.';
 }
 local.addEventListener('change',atualizar);
 atualizar();
})();
</script>
<?php
include __DIR__ . '/../includes/under-bar.php';
include __DIR__ . '/../includes/footer.php';
?>
