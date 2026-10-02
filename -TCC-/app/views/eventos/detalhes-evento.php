<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/_escalacao-evento.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$idEvento = filter_var($_GET['id_evento'] ?? null, FILTER_VALIDATE_INT);

if (!$idEvento || $idEvento < 1) {
    http_response_code(404);
    exit('Evento não encontrado.');
}

if (empty($_SESSION['csrf_eventos'])) {
    $_SESSION['csrf_eventos'] = bin2hex(random_bytes(32));
}

function escaparDetalhe($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

try {
    $evento = evento_buscar($conn, $idEvento);

    if (!$evento) {
        http_response_code(404);
        exit('Evento não encontrado.');
    }

    $solicitacoes = evento_buscar_solicitacoes($conn, $idEvento);
    $confirmadosSemVaga = evento_buscar_confirmados_sem_vaga($conn, $idEvento);
} catch (PDOException $e) {
    error_log('Erro ao carregar detalhes do evento: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar o evento. Verifique se a atualização do banco foi executada.');
}

$estado = evento_montar_escalacao($solicitacoes, $idUsuario);
$confirmadosTimes = $estado['confirmados_times'];
$pendentesPorVaga = $estado['pendentes_por_vaga'];
$confirmadosIndividuais = $estado['confirmados_individuais'];
$pendentes = $estado['pendentes'];
$minhaSolicitacao = $estado['minha_solicitacao'];

$organizador = (int) $evento['id_criador'] === $idUsuario;
$aberto = $evento['status_evento'] === 'ativo' && (bool) $evento['aberto'];
$limite = evento_vagas_por_time((string) $evento['nome_esporte']);
$totalConfirmados = count($confirmadosSemVaga);

if ($limite !== null) {
    $totalConfirmados += count($confirmadosTimes[1]) + count($confirmadosTimes[2]);
} else {
    $totalConfirmados += count($confirmadosIndividuais);
}

$minhaParticipacaoSemVaga = null;
foreach ($confirmadosSemVaga as $participanteSemVaga) {
    if ((int) $participanteSemVaga['id_user'] === $idUsuario) {
        $minhaParticipacaoSemVaga = $participanteSemVaga;
        break;
    }
}

$flash = $_SESSION['flash_vaga_evento'][$idEvento] ?? null;
unset($_SESSION['flash_vaga_evento'][$idEvento]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escaparDetalhe($evento['nome_evento']) ?> | Zubbo</title>
    <script>
        if (localStorage.getItem('zubbo-tema') === 'escuro') {
            document.documentElement.classList.add('tema-escuro');
        }
    </script>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/eventos.css">
</head>
<body>
<main class="eventos-container evento-detalhes-shell">
    <a class="eventos-voltar" href="eventos.php">← Voltar aos eventos</a>

    <section class="evento-score-hero">
        <div class="evento-score-faixa">
            <div>
                <p class="eventos-marca"><?= escaparDetalhe($evento['nome_esporte']) ?></p>
                <h1><?= escaparDetalhe($evento['nome_evento']) ?></h1>
                <p>
                    <?= escaparDetalhe($evento['nome_local']) ?> ·
                    <?= escaparDetalhe(date('d/m/Y', strtotime($evento['data_evento']))) ?> às
                    <?= escaparDetalhe(substr($evento['horario_evento'], 0, 5)) ?>
                </p>
            </div>

            <span class="evento-score-status <?= !$aberto ? 'fechado' : '' ?>">
                <?= $evento['status_evento'] === 'cancelado' ? 'CANCELADO' : ($aberto ? 'ABERTO' : 'ENCERRADO') ?>
            </span>
        </div>

        <div class="evento-score-resumo">
            <div><span>ORGANIZADOR</span><strong><?= escaparDetalhe($evento['criador'] ?? 'Indisponível') ?></strong></div>
            <div><span>CONFIRMADOS</span><strong><?= $totalConfirmados ?><?= $limite ? ' / ' . ($limite * 2) : '' ?></strong></div>
            <div><span>SOLICITAÇÕES</span><strong><?= count($pendentes) ?></strong></div>
            <div><span>LOCAL</span><strong><?= escaparDetalhe($evento['endereco_local']) ?></strong></div>
        </div>
    </section>

    <?php if ($flash): ?>
        <p
            class="evento-aviso <?= $flash['tipo'] === 'sucesso' ? 'evento-aviso-sucesso' : '' ?>"
            role="<?= $flash['tipo'] === 'sucesso' ? 'status' : 'alert' ?>"
        >
            <?= escaparDetalhe($flash['mensagem']) ?>
        </p>
    <?php endif; ?>

    <?php if ($confirmadosSemVaga): ?>
        <section class="evento-legado-aviso">
            <strong><?= count($confirmadosSemVaga) ?> participação(ões) do sistema antigo ainda sem vaga.</strong>
            <p>
                Quem já estava confirmado continua na lista. Ao escolher uma vaga no novo quadro,
                a participação antiga será vinculada automaticamente sem precisar de nova aprovação.
            </p>

            <?php if ($minhaParticipacaoSemVaga): ?>
                <form action="vaga-evento-acao.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="origem" value="detalhes">
                    <button class="evento-botao-secundario" type="submit">Cancelar minha participação</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($limite !== null): ?>
        <div class="evento-score-acoes">
            <div>
                <strong>Escalação <?= $limite ?> x <?= $limite ?></strong>
                <span>Escolha uma vaga livre. Ela só fica ocupada depois da aprovação do organizador.</span>
            </div>
            <a class="eventos-criar" href="lista-presenca.php?id_evento=<?= $idEvento ?>">Abrir escalação completa</a>
        </div>

        <?php if ($minhaSolicitacao): ?>
            <section class="evento-minha-vaga <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'confirmada' : '' ?>">
                <div>
                    <span>SUA PARTICIPAÇÃO</span>
                    <strong>
                        Time <?= (int) $minhaSolicitacao['time_num'] ?> ·
                        #<?= str_pad((string) $minhaSolicitacao['numero_vaga'], 2, '0', STR_PAD_LEFT) ?>
                    </strong>
                    <small>
                        <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada'
                            ? 'Confirmado pelo organizador'
                            : 'Aguardando aprovação' ?>
                    </small>
                </div>
                <form action="vaga-evento-acao.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="origem" value="detalhes">
                    <button class="evento-botao-secundario" type="submit">
                        <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada'
                            ? 'Sair da escalação'
                            : 'Cancelar solicitação' ?>
                    </button>
                </form>
            </section>
        <?php endif; ?>

        <section class="evento-times-preview">
            <?php foreach ([1, 2] as $time): ?>
                <article class="evento-time-preview evento-time-<?= $time ?>">
                    <header>
                        <div>
                            <span>TEAM <?= $time === 1 ? 'A' : 'B' ?></span>
                            <h2>TIME <?= $time ?></h2>
                        </div>
                        <strong><?= count($confirmadosTimes[$time]) ?> / <?= $limite ?></strong>
                    </header>

                    <ol>
                        <?php for ($vaga = 1; $vaga <= $limite; $vaga++): ?>
                            <?php
                                $ocupante = $confirmadosTimes[$time][$vaga] ?? null;
                                $quantidadePendentes = count($pendentesPorVaga[$time][$vaga] ?? []);
                            ?>
                            <li class="<?= $ocupante ? 'ocupada status-aprovada' : 'livre' ?>">
                                <span class="evento-slot-numero"><?= str_pad((string) $vaga, 2, '0', STR_PAD_LEFT) ?></span>

                                <?php if ($ocupante): ?>
                                    <span class="evento-slot-avatar"><?= escaparDetalhe(evento_iniciais($ocupante['nome_user'])) ?></span>
                                    <span class="evento-slot-jogador">
                                        <strong><?= escaparDetalhe($ocupante['nome_user']) ?></strong>
                                        <small>CONFIRMADO</small>
                                    </span>
                                <?php else: ?>
                                    <span class="evento-slot-avatar evento-slot-vazio">+</span>
                                    <span class="evento-slot-jogador">
                                        <strong>Vaga livre</strong>
                                        <small><?= $quantidadePendentes ? $quantidadePendentes . ' SOLICITAÇÃO(ÕES)' : 'DISPONÍVEL' ?></small>
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                    </ol>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="evento-individual-card">
            <div>
                <p class="eventos-marca">PARTICIPAÇÃO INDIVIDUAL</p>
                <h2><?= escaparDetalhe($evento['nome_esporte']) ?></h2>
                <p>Esta modalidade não usa Time 1 e Time 2. A entrada continua dependendo da aprovação do organizador.</p>
            </div>

            <?php if (!$minhaSolicitacao && $aberto): ?>
                <form action="vaga-evento-acao.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                    <input type="hidden" name="acao" value="solicitar">
                    <input type="hidden" name="origem" value="detalhes">
                    <button class="eventos-criar" type="submit">Solicitar participação</button>
                </form>
            <?php elseif ($minhaSolicitacao): ?>
                <div class="evento-individual-status">
                    <strong>
                        <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada'
                            ? 'Participação confirmada'
                            : 'Solicitação pendente' ?>
                    </strong>
                    <form action="vaga-evento-acao.php" method="post">
                        <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                        <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                        <input type="hidden" name="acao" value="cancelar">
                        <input type="hidden" name="origem" value="detalhes">
                        <button class="evento-botao-secundario" type="submit">Cancelar participação</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($confirmadosIndividuais || $confirmadosSemVaga): ?>
            <section class="eventos-card evento-detalhes">
                <h2>Participantes confirmados</h2>
                <ul class="evento-integrantes">
                    <?php foreach ($confirmadosIndividuais as $participante): ?>
                        <li><?= escaparDetalhe($participante['nome_user']) ?></li>
                    <?php endforeach; ?>
                    <?php foreach ($confirmadosSemVaga as $participante): ?>
                        <li><?= escaparDetalhe($participante['nome_user']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($organizador): ?>
        <section class="evento-solicitacoes-card">
            <div class="evento-solicitacoes-titulo">
                <div>
                    <span>ORGANIZADOR</span>
                    <h2>Solicitações pendentes</h2>
                </div>
                <strong><?= count($pendentes) ?></strong>
            </div>

            <?php if (!$pendentes): ?>
                <p class="evento-solicitacoes-vazio">Nenhuma solicitação aguardando resposta.</p>
            <?php else: ?>
                <div class="evento-solicitacoes-lista">
                    <?php foreach ($pendentes as $solicitacao): ?>
                        <article>
                            <span class="evento-slot-avatar"><?= escaparDetalhe(evento_iniciais($solicitacao['nome_user'])) ?></span>
                            <div>
                                <strong><?= escaparDetalhe($solicitacao['nome_user']) ?></strong>
                                <small>
                                    <?php if ($solicitacao['time_num'] !== null): ?>
                                        Time <?= (int) $solicitacao['time_num'] ?> · vaga #<?= (int) $solicitacao['numero_vaga'] ?>
                                    <?php else: ?>
                                        Participação individual
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="evento-solicitacao-acoes">
                                <form action="vaga-evento-acao.php" method="post">
                                    <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                                    <input type="hidden" name="id_solicitacao" value="<?= (int) $solicitacao['id_solicitacao'] ?>">
                                    <input type="hidden" name="acao" value="aprovar">
                                    <input type="hidden" name="origem" value="detalhes">
                                    <button class="evento-aprovar" type="submit">Aceitar</button>
                                </form>
                                <form action="vaga-evento-acao.php" method="post">
                                    <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                                    <input type="hidden" name="id_solicitacao" value="<?= (int) $solicitacao['id_solicitacao'] ?>">
                                    <input type="hidden" name="acao" value="recusar">
                                    <input type="hidden" name="origem" value="detalhes">
                                    <button class="evento-recusar" type="submit">Recusar</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php
require __DIR__ . '/../includes/under-bar.php';
require __DIR__ . '/../includes/footer.php';
?>