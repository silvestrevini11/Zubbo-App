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

function escaparDetalhe($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

try {
    $stmt = $conn->prepare("
        SELECT ev.*, e.nome_esporte, l.nome_local, l.endereco_local,
               u.nome_user AS criador,
               (TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()) AS aberto
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
    error_log('Erro ao carregar detalhes do evento: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar o evento. Verifique se a atualização do banco foi executada.');
}

$organizador = (int) $evento['id_criador'] === $idUsuario;
$aberto = $evento['status_evento'] === 'ativo' && (bool) $evento['aberto'];
$limite = evento_vagas_por_time((string) $evento['nome_esporte']);
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
                <p><?= escaparDetalhe($evento['nome_local']) ?> · <?= escaparDetalhe(date('d/m/Y', strtotime($evento['data_evento']))) ?> às <?= escaparDetalhe(substr($evento['horario_evento'], 0, 5)) ?></p>
            </div>
            <span class="evento-score-status <?= !$aberto ? 'fechado' : '' ?>">
                <?= $evento['status_evento'] === 'cancelado' ? 'CANCELADO' : ($aberto ? 'ABERTO' : 'ENCERRADO') ?>
            </span>
        </div>

        <div class="evento-score-resumo">
            <div><span>ORGANIZADOR</span><strong><?= escaparDetalhe($evento['criador'] ?? 'Indisponível') ?></strong></div>
            <div><span>CONFIRMADOS</span><strong><?= $confirmados ?><?= $limite ? ' / ' . ($limite * 2) : '' ?></strong></div>
            <div><span>SOLICITAÇÕES</span><strong><?= count($pendentes) ?></strong></div>
            <div><span>LOCAL</span><strong><?= escaparDetalhe($evento['endereco_local']) ?></strong></div>
        </div>
    </section>

    <?php if ($flash): ?>
        <p class="evento-aviso <?= $flash['tipo'] === 'sucesso' ? 'evento-aviso-sucesso' : '' ?>" role="status">
            <?= escaparDetalhe($flash['mensagem']) ?>
        </p>
    <?php endif; ?>

    <?php if ($limite === null): ?>
        <section class="eventos-card evento-detalhes">
            <h2>Escalação não disponível</h2>
            <p class="eventos-subtitulo">
                <?= escaparDetalhe($evento['nome_esporte']) ?> não possui uma formação fixa de dois times configurada.
            </p>
        </section>
    <?php else: ?>
        <div class="evento-score-acoes">
            <div>
                <strong>Escalação <?= $limite ?> x <?= $limite ?></strong>
                <span>Escolha uma vaga livre e envie sua solicitação ao organizador.</span>
            </div>
            <a class="eventos-criar" href="lista-presenca.php?id_evento=<?= $idEvento ?>">Abrir escalação completa</a>
        </div>

        <?php if ($minhaSolicitacao): ?>
            <section class="evento-minha-vaga <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'confirmada' : '' ?>">
                <div>
                    <span>SUA VAGA</span>
                    <strong>
                        Time <?= (int) $minhaSolicitacao['time_num'] ?> · #<?= str_pad((string) $minhaSolicitacao['numero_vaga'], 2, '0', STR_PAD_LEFT) ?>
                    </strong>
                    <small><?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'Confirmado pelo organizador' : 'Aguardando aprovação' ?></small>
                </div>
                <form action="vaga-evento-acao.php" method="post">
                    <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                    <input type="hidden" name="id_evento" value="<?= $idEvento ?>">
                    <input type="hidden" name="acao" value="cancelar">
                    <input type="hidden" name="origem" value="detalhes">
                    <button class="evento-botao-secundario" type="submit">
                        <?= $minhaSolicitacao['status_solicitacao'] === 'aprovada' ? 'Sair da escalação' : 'Cancelar solicitação' ?>
                    </button>
                </form>
            </section>
        <?php endif; ?>

        <section class="evento-times-preview">
            <?php foreach ([1, 2] as $time): ?>
                <article class="evento-time-preview evento-time-<?= $time ?>">
                    <header>
                        <div>
                            <span>TEAM</span>
                            <h2>TIME <?= $time ?></h2>
                        </div>
                        <strong>
                            <?= count(array_filter($times[$time], fn($vaga) => $vaga['status_solicitacao'] === 'aprovada')) ?>
                            / <?= $limite ?>
                        </strong>
                    </header>

                    <ol>
                        <?php for ($vaga = 1; $vaga <= $limite; $vaga++): ?>
                            <?php $ocupante = $times[$time][$vaga] ?? null; ?>
                            <li class="<?= $ocupante ? 'ocupada status-' . escaparDetalhe($ocupante['status_solicitacao']) : 'livre' ?>">
                                <span class="evento-slot-numero"><?= str_pad((string) $vaga, 2, '0', STR_PAD_LEFT) ?></span>

                                <?php if ($ocupante): ?>
                                    <span class="evento-slot-avatar"><?= escaparDetalhe(evento_iniciais($ocupante['nome_user'])) ?></span>
                                    <span class="evento-slot-jogador">
                                        <strong>
                                            <?= $ocupante['status_solicitacao'] === 'aprovada'
                                                ? escaparDetalhe($ocupante['nome_user'])
                                                : ($organizador || (int) $ocupante['id_user'] === $idUsuario
                                                    ? escaparDetalhe($ocupante['nome_user'])
                                                    : 'Solicitação em análise') ?>
                                        </strong>
                                        <small><?= $ocupante['status_solicitacao'] === 'aprovada' ? 'CONFIRMADO' : 'PENDENTE' ?></small>
                                    </span>
                                <?php else: ?>
                                    <span class="evento-slot-avatar evento-slot-vazio">+</span>
                                    <span class="evento-slot-jogador"><strong>Vaga livre</strong><small>DISPONÍVEL</small></span>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                    </ol>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($organizador && $pendentes): ?>
            <section class="evento-solicitacoes-card">
                <div class="evento-solicitacoes-titulo">
                    <div>
                        <span>ORGANIZADOR</span>
                        <h2>Solicitações pendentes</h2>
                    </div>
                    <strong><?= count($pendentes) ?></strong>
                </div>

                <div class="evento-solicitacoes-lista">
                    <?php foreach ($pendentes as $solicitacao): ?>
                        <article>
                            <span class="evento-slot-avatar"><?= escaparDetalhe(evento_iniciais($solicitacao['nome_user'])) ?></span>
                            <div>
                                <strong><?= escaparDetalhe($solicitacao['nome_user']) ?></strong>
                                <small>Time <?= (int) $solicitacao['time_num'] ?> · vaga #<?= (int) $solicitacao['numero_vaga'] ?></small>
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
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php
require __DIR__ . '/../includes/under-bar.php';
require __DIR__ . '/../includes/footer.php';
?>
