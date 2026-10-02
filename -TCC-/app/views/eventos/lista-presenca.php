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

function escaparEscalacao($valor): string
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
    error_log('Erro ao carregar escalação: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar a escalação. Verifique se a atualização do banco foi executada.');
}

$estado = evento_montar_escalacao($solicitacoes, $idUsuario);
$confirmadosTimes = $estado['confirmados_times'];
$pendentesPorVaga = $estado['pendentes_por_vaga'];
$confirmadosIndividuais = $estado['confirmados_individuais'];
$pendentes = $estado['pendentes'];
$minhaSolicitacao = $estado['minha_solicitacao'];

$limite = evento_vagas_por_time((string) $evento['nome_esporte']);
$organizador = (int) $evento['id_criador'] === $idUsuario;
$aberto = $evento['status_evento'] === 'ativo' && (bool) $evento['aberto'];
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

$totalVagas = $limite !== null ? $limite * 2 : null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escalação | <?= escaparEscalacao($evento['nome_evento']) ?></title>
    <script>
        if (localStorage.getItem('zubbo-tema') === 'escuro') {
            document.documentElement.classList.add('tema-escuro');
        }
    </script>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/eventos.css">
    <link rel="stylesheet" href="../../../public/css/lista-presenca.css">
</head>
<body>
<main class="eventos-container presenca-container">
    <a class="eventos-voltar" href="detalhes-evento.php?id_evento=<?= $idEvento ?>">← Voltar ao evento</a>

    <header class="presenca-score-topo">
        <div>
            <span class="presenca-kicker"><?= escaparEscalacao($evento['nome_esporte']) ?></span>
            <h1><?= escaparEscalacao($evento['nome_evento']) ?></h1>
            <p>
                <?= escaparEscalacao($evento['nome_local']) ?> ·
                <?= escaparEscalacao(date('d/m/Y', strtotime($evento['data_evento']))) ?> ·
                <?= escaparEscalacao(substr($evento['horario_evento'], 0, 5)) ?>
            </p>
        </div>

        <div class="presenca-score-numeros">
            <span><b><?= $totalConfirmados ?></b> CONFIRMADOS</span>
            <span><b><?= count($pendentes) ?></b> SOLICITAÇÕES</span>
            <?php if ($totalVagas !== null): ?>
                <span><b><?= $totalVagas ?></b> VAGAS</span>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($flash): ?>
        <p
            class="presenca-flash <?= $flash['tipo'] === 'sucesso' ? 'sucesso' : 'erro' ?>"
            role="<?= $flash['tipo'] === 'sucesso' ? 'status' : 'alert' ?>"
        >
            <?= escaparEscalacao($flash['mensagem']) ?>
        </p>
    <?php endif; ?>

    <?php if ($confirmadosSemVaga): ?>
        <section class="presenca-legado">
            <strong><?= count($confirmadosSemVaga) ?> participação(ões) antigas ainda não foram posicionadas.</strong>
            <p>Essas pessoas continuam confirmadas e podem escolher uma vaga sem passar por nova aprovação.</p>

            <?php if ($minhaParticipacaoSemVaga): ?>
                <form action="vaga-evento-acao.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="origem" value="lista">
                    <button type="submit">Cancelar minha participação</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($limite !== null): ?>
        <section class="presenca-instrucoes">
            <div>
                <strong>Escolha sua vaga</strong>
                <span>Pedidos pendentes não bloqueiam o slot. A vaga só fecha quando um jogador é aprovado.</span>
            </div>
            <div class="presenca-legenda">
                <span class="livre">Livre</span>
                <span class="pendente">Com pedidos</span>
                <span class="confirmado">Confirmado</span>
            </div>
        </section>

        <?php if ($minhaSolicitacao): ?>
            <section class="presenca-minha-solicitacao <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'confirmada' : '' ?>">
                <div>
                    <span><?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'VOCÊ ESTÁ NO JOGO' : 'SUA SOLICITAÇÃO' ?></span>
                    <strong>
                        Time <?= (int) $minhaSolicitacao['time_num'] ?> ·
                        Vaga <?= (int) $minhaSolicitacao['numero_vaga'] ?>
                    </strong>
                    <small>
                        <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada'
                            ? 'Confirmada pelo organizador'
                            : 'Aguardando resposta do organizador' ?>
                    </small>
                </div>
                <form action="vaga-evento-acao.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="origem" value="lista">
                    <button type="submit">
                        <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'Sair do time' : 'Cancelar pedido' ?>
                    </button>
                </form>
            </section>
        <?php endif; ?>

        <section class="presenca-times" aria-label="Escalação dos dois times">
            <?php foreach ([1, 2] as $time): ?>
                <article class="presenca-time presenca-time-<?= $time ?>">
                    <header>
                        <div class="presenca-time-identidade">
                            <span>TEAM <?= $time === 1 ? 'A' : 'B' ?></span>
                            <h2>TIME <?= $time ?></h2>
                        </div>
                        <div class="presenca-time-contador">
                            <strong><?= count($confirmadosTimes[$time]) ?></strong>
                            <span>/ <?= $limite ?></span>
                        </div>
                    </header>

                    <div class="presenca-colunas" aria-hidden="true">
                        <span>VAGA</span>
                        <span>JOGADOR</span>
                        <span>STATUS</span>
                    </div>

                    <ol class="presenca-slots">
                        <?php for ($vaga = 1; $vaga <= $limite; $vaga++): ?>
                            <?php
                                $ocupante = $confirmadosTimes[$time][$vaga] ?? null;
                                $pedidosDaVaga = $pendentesPorVaga[$time][$vaga] ?? [];
                                $quantidadePedidos = count($pedidosDaVaga);
                                $minhaVagaPendente = false;

                                foreach ($pedidosDaVaga as $pedido) {
                                    if ((int) $pedido['id_user'] === $idUsuario) {
                                        $minhaVagaPendente = true;
                                        break;
                                    }
                                }

                                $classesSlot = $ocupante ? 'status-aprovada' : 'status-livre';

                                if (!$ocupante && $quantidadePedidos > 0) {
                                    $classesSlot .= ' tem-pedidos';
                                }

                                if ($organizador && $ocupante) {
                                    $classesSlot .= ' tem-remocao';
                                }
                            ?>
                            <li class="presenca-slot <?= $classesSlot ?>">
                                <span class="presenca-slot-numero"><?= str_pad((string) $vaga, 2, '0', STR_PAD_LEFT) ?></span>

                                <?php if ($ocupante): ?>
                                    <span class="presenca-slot-avatar"><?= escaparEscalacao(evento_iniciais($ocupante['nome_user'])) ?></span>
                                    <span class="presenca-slot-nome">
                                        <strong><?= escaparEscalacao($ocupante['nome_user']) ?></strong>
                                        <small><?= (int) $ocupante['id_user'] === $idUsuario ? 'VOCÊ' : 'ZUBBO PLAYER' ?></small>
                                    </span>

                                    <span class="presenca-slot-status confirmado">CONFIRMADO</span>

                                    <?php if ($organizador): ?>
                                        <form class="presenca-slot-acao" action="vaga-evento-acao.php" method="post">
                                            <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                                            <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                                            <input type="hidden" name="id_solicitacao" value="<?= (int) $ocupante['id_solicitacao'] ?>">
                                            <input type="hidden" name="acao" value="remover">
                                            <input type="hidden" name="origem" value="lista">
                                            <button type="submit" aria-label="Remover <?= escaparEscalacao($ocupante['nome_user']) ?> da vaga">×</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="presenca-slot-avatar vazio">+</span>
                                    <span class="presenca-slot-nome">
                                        <strong>Vaga disponível</strong>
                                        <small>
                                            <?php if ($minhaVagaPendente): ?>
                                                SEU PEDIDO ESTÁ EM ANÁLISE
                                            <?php elseif ($quantidadePedidos > 0): ?>
                                                <?= $quantidadePedidos ?> SOLICITAÇÃO(ÕES)
                                            <?php else: ?>
                                                TIME <?= $time ?> · SLOT <?= $vaga ?>
                                            <?php endif; ?>
                                        </small>
                                    </span>

                                    <div class="presenca-slot-livre-acoes">
                                        <?php if ($quantidadePedidos > 0): ?>
                                            <span class="presenca-candidatos"><?= $quantidadePedidos ?> pedido<?= $quantidadePedidos === 1 ? '' : 's' ?></span>
                                        <?php endif; ?>

                                        <?php if ($aberto && !$minhaSolicitacao): ?>
                                            <form class="presenca-solicitar" action="vaga-evento-acao.php" method="post">
                                                <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                                                <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                                                <input type="hidden" name="time_num" value="<?= $time ?>">
                                                <input type="hidden" name="numero_vaga" value="<?= $vaga ?>">
                                                <input type="hidden" name="acao" value="solicitar">
                                                <input type="hidden" name="origem" value="lista">
                                                <button type="submit">SOLICITAR</button>
                                            </form>
                                        <?php elseif (!$minhaSolicitacao): ?>
                                            <span class="presenca-slot-status livre">FECHADO</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                    </ol>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="presenca-individual">
            <div class="presenca-individual-topo">
                <div>
                    <span>MODALIDADE INDIVIDUAL</span>
                    <h2>Lista de participantes</h2>
                    <p>A participação precisa ser aprovada pelo organizador.</p>
                </div>

                <?php if (!$minhaSolicitacao && !$minhaParticipacaoSemVaga && $aberto): ?>
                    <form action="vaga-evento-acao.php" method="post">
                        <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                        <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                        <input type="hidden" name="acao" value="solicitar">
                        <input type="hidden" name="origem" value="lista">
                        <button type="submit">SOLICITAR PARTICIPAÇÃO</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($minhaSolicitacao): ?>
                <div class="presenca-individual-minha">
                    <strong>
                        <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada'
                            ? 'Sua participação está confirmada.'
                            : 'Sua solicitação está aguardando aprovação.' ?>
                    </strong>
                    <form action="vaga-evento-acao.php" method="post">
                        <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                        <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                        <input type="hidden" name="acao" value="cancelar">
                        <input type="hidden" name="origem" value="lista">
                        <button type="submit">Cancelar</button>
                    </form>
                </div>
            <?php endif; ?>

            <ul class="presenca-individual-lista">
                <?php foreach ($confirmadosIndividuais as $participante): ?>
                    <li>
                        <span class="presenca-slot-avatar"><?= escaparEscalacao(evento_iniciais($participante['nome_user'])) ?></span>
                        <strong><?= escaparEscalacao($participante['nome_user']) ?></strong>
                        <span class="presenca-slot-status confirmado">CONFIRMADO</span>
                    </li>
                <?php endforeach; ?>

                <?php foreach ($confirmadosSemVaga as $participante): ?>
                    <li>
                        <span class="presenca-slot-avatar"><?= escaparEscalacao(evento_iniciais($participante['nome_user'])) ?></span>
                        <strong><?= escaparEscalacao($participante['nome_user']) ?></strong>
                        <span class="presenca-slot-status confirmado">CONFIRMADO</span>
                    </li>
                <?php endforeach; ?>

                <?php if (!$confirmadosIndividuais && !$confirmadosSemVaga): ?>
                    <li class="presenca-individual-vazio">Nenhum participante confirmado ainda.</li>
                <?php endif; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($organizador): ?>
        <section class="presenca-moderacao">
            <header>
                <div>
                    <span>ORGANIZADOR</span>
                    <h2>Fila de solicitações</h2>
                </div>
                <strong><?= count($pendentes) ?></strong>
            </header>

            <?php if (!$pendentes): ?>
                <p class="presenca-fila-vazia">Nenhuma solicitação aguardando resposta.</p>
            <?php else: ?>
                <div class="presenca-fila">
                    <?php foreach ($pendentes as $solicitacao): ?>
                        <article>
                            <span class="presenca-slot-avatar"><?= escaparEscalacao(evento_iniciais($solicitacao['nome_user'])) ?></span>
                            <div class="presenca-fila-pessoa">
                                <strong><?= escaparEscalacao($solicitacao['nome_user']) ?></strong>
                                <span>
                                    <?php if ($solicitacao['time_num'] !== null): ?>
                                        Time <?= (int) $solicitacao['time_num'] ?> · vaga <?= (int) $solicitacao['numero_vaga'] ?>
                                    <?php else: ?>
                                        Participação individual
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="presenca-fila-acoes">
                                <form action="vaga-evento-acao.php" method="post">
                                    <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                                    <input type="hidden" name="id_solicitacao" value="<?= (int) $solicitacao['id_solicitacao'] ?>">
                                    <input type="hidden" name="acao" value="aprovar">
                                    <input type="hidden" name="origem" value="lista">
                                    <button class="aceitar" type="submit">Aceitar</button>
                                </form>
                                <form action="vaga-evento-acao.php" method="post">
                                    <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                                    <input type="hidden" name="id_solicitacao" value="<?= (int) $solicitacao['id_solicitacao'] ?>">
                                    <input type="hidden" name="acao" value="recusar">
                                    <input type="hidden" name="origem" value="lista">
                                    <button class="recusar" type="submit">Recusar</button>
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
