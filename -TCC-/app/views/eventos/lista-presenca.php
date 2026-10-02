<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/_regras-equipes.php';

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
    $stmt = $conn->prepare("
        SELECT ev.id_evento, ev.nome_evento, ev.data_evento, ev.horario_evento,
               ev.status_evento, ev.id_criador,
               (TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()) AS aberto,
               e.nome_esporte,
               l.nome_local, l.endereco_local,
               u.nome_user AS criador
        FROM Evento ev
        INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
        INNER JOIN LocalEsp l ON l.id_local = ev.id_local
        LEFT JOIN Usuario u ON u.id_user = ev.id_criador
        WHERE ev.id_evento = ? AND ev.status_evento <> 'removido'
        LIMIT 1
    ");
    $stmt->execute([$idEvento]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        http_response_code(404);
        exit('Evento não encontrado.');
    }

    $stmt = $conn->prepare("
        SELECT s.id_solicitacao, s.id_user, s.time_num, s.numero_vaga,
               s.status_solicitacao, s.data_solicitacao,
               u.nome_user
        FROM Solicitacao_Vaga_Evento s
        INNER JOIN Usuario u ON u.id_user = s.id_user
        WHERE s.id_evento = ?
          AND s.status_solicitacao IN ('pendente', 'aprovada')
        ORDER BY s.time_num, s.numero_vaga
    ");
    $stmt->execute([$idEvento]);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao carregar escalação: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar a escalação. Verifique se a atualização do banco foi executada.');
}

$limite = evento_vagas_por_time((string) $evento['nome_esporte']);
$organizador = (int) $evento['id_criador'] === $idUsuario;
$aberto = $evento['status_evento'] === 'ativo' && (bool) $evento['aberto'];
$times = [1 => [], 2 => []];
$pendentes = [];
$confirmados = 0;
$minhaSolicitacao = null;

foreach ($solicitacoes as $solicitacao) {
    $time = (int) $solicitacao['time_num'];
    $vaga = (int) $solicitacao['numero_vaga'];

    if (isset($times[$time])) {
        $times[$time][$vaga] = $solicitacao;
    }

    if ($solicitacao['status_solicitacao'] === 'aprovada') {
        $confirmados++;
    } else {
        $pendentes[] = $solicitacao;
    }

    if ((int) $solicitacao['id_user'] === $idUsuario) {
        $minhaSolicitacao = $solicitacao;
    }
}

$flash = $_SESSION['flash_vaga_evento'][$idEvento] ?? null;
unset($_SESSION['flash_vaga_evento'][$idEvento]);

$totalVagas = $limite ? $limite * 2 : 0;
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
            <p><?= escaparEscalacao($evento['nome_local']) ?> · <?= escaparEscalacao(date('d/m/Y', strtotime($evento['data_evento']))) ?> · <?= escaparEscalacao(substr($evento['horario_evento'], 0, 5)) ?></p>
        </div>
        <div class="presenca-score-numeros">
            <span><b><?= $confirmados ?></b> CONFIRMADOS</span>
            <span><b><?= count($pendentes) ?></b> PENDENTES</span>
            <?php if ($limite): ?><span><b><?= $totalVagas ?></b> VAGAS</span><?php endif; ?>
        </div>
    </header>

    <?php if ($flash): ?>
        <p class="presenca-flash <?= $flash['tipo'] === 'sucesso' ? 'sucesso' : 'erro' ?>" role="status">
            <?= escaparEscalacao($flash['mensagem']) ?>
        </p>
    <?php endif; ?>

    <?php if ($limite === null): ?>
        <section class="presenca-sem-times">
            <strong>Modalidade individual</strong>
            <p><?= escaparEscalacao($evento['nome_esporte']) ?> não possui uma quantidade fixa de integrantes por time nesta escalação.</p>
        </section>
    <?php else: ?>
        <section class="presenca-instrucoes">
            <div>
                <strong>Escolha sua vaga</strong>
                <span>Cada pessoa pode ter uma única solicitação ativa por evento.</span>
            </div>
            <div class="presenca-legenda">
                <span class="livre">Livre</span>
                <span class="pendente">Pendente</span>
                <span class="confirmado">Confirmado</span>
            </div>
        </section>

        <?php if ($minhaSolicitacao): ?>
            <section class="presenca-minha-solicitacao <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'confirmada' : '' ?>">
                <div>
                    <span><?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'VOCÊ ESTÁ NO JOGO' : 'SUA SOLICITAÇÃO' ?></span>
                    <strong>Time <?= (int) $minhaSolicitacao['time_num'] ?> · Vaga <?= (int) $minhaSolicitacao['numero_vaga'] ?></strong>
                    <small><?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'Confirmada pelo organizador' : 'Aguardando resposta do organizador' ?></small>
                </div>
                <form action="vaga-evento-acao.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="origem" value="lista">
                    <button type="submit"><?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'Sair do time' : 'Cancelar pedido' ?></button>
                </form>
            </section>
        <?php endif; ?>

        <section class="presenca-times" aria-label="Escalação dos dois times">
            <?php foreach ([1, 2] as $time): ?>
                <?php
                    $confirmadosTime = count(array_filter(
                        $times[$time],
                        fn($vaga) => $vaga['status_solicitacao'] === 'aprovada'
                    ));
                ?>
                <article class="presenca-time presenca-time-<?= $time ?>">
                    <header>
                        <div class="presenca-time-identidade">
                            <span>TEAM <?= $time === 1 ? 'A' : 'B' ?></span>
                            <h2>TIME <?= $time ?></h2>
                        </div>
                        <div class="presenca-time-contador">
                            <strong><?= $confirmadosTime ?></strong>
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
                                $ocupante = $times[$time][$vaga] ?? null;
                                $status = $ocupante['status_solicitacao'] ?? 'livre';
                                $minhaVaga = $ocupante && (int) $ocupante['id_user'] === $idUsuario;
                            ?>
                            <li class="presenca-slot status-<?= escaparEscalacao($status) ?>">
                                <span class="presenca-slot-numero"><?= str_pad((string) $vaga, 2, '0', STR_PAD_LEFT) ?></span>

                                <?php if ($ocupante): ?>
                                    <span class="presenca-slot-avatar"><?= escaparEscalacao(evento_iniciais($ocupante['nome_user'])) ?></span>
                                    <span class="presenca-slot-nome">
                                        <strong>
                                            <?php if ($status === 'aprovada' || $organizador || $minhaVaga): ?>
                                                <?= escaparEscalacao($ocupante['nome_user']) ?>
                                            <?php else: ?>
                                                Solicitação em análise
                                            <?php endif; ?>
                                        </strong>
                                        <small><?= $minhaVaga ? 'VOCÊ' : 'ZUBBO PLAYER' ?></small>
                                    </span>

                                    <span class="presenca-slot-status <?= $status === 'aprovada' ? 'confirmado' : 'pendente' ?>">
                                        <?= $status === 'aprovada' ? 'CONFIRMADO' : 'PENDENTE' ?>
                                    </span>

                                    <?php if ($organizador && $status === 'aprovada'): ?>
                                        <form class="presenca-slot-acao" action="vaga-evento-acao.php" method="post">
                                            <input type="hidden" name="csrf" value="<?= escaparEscalacao($_SESSION['csrf_eventos']) ?>">
                                            <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                                            <input type="hidden" name="id_solicitacao" value="<?= (int) $ocupante['id_solicitacao'] ?>">
                                            <input type="hidden" name="acao" value="remover">
                                            <input type="hidden" name="origem" value="lista">
                                            <button type="submit" title="Remover jogador">×</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="presenca-slot-avatar vazio">+</span>
                                    <span class="presenca-slot-nome">
                                        <strong>Vaga disponível</strong>
                                        <small>TIME <?= $time ?> · SLOT <?= $vaga ?></small>
                                    </span>

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
                                    <?php else: ?>
                                        <span class="presenca-slot-status livre"><?= $aberto ? 'LIVRE' : 'FECHADO' ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                    </ol>
                </article>
            <?php endforeach; ?>
        </section>

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
                                    <span>Time <?= (int) $solicitacao['time_num'] ?> · vaga <?= (int) $solicitacao['numero_vaga'] ?></span>
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
    <?php endif; ?>
</main>

<?php
require __DIR__ . '/../includes/under-bar.php';
require __DIR__ . '/../includes/footer.php';
?>
