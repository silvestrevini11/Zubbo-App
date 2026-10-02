<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$idEvento = filter_var($_GET['id_evento'] ?? null, FILTER_VALIDATE_INT);

if (!$idEvento || $idEvento < 1) {
    http_response_code(404);
    exit('Evento não encontrado.');
}

function escaparListaPresenca($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$criteriosEquipe = [
    'futebol' => 11,
    'futsal' => 5,
    'vôlei' => 6,
    'basquete' => 5,
    'handebol' => 7,
];

try {
    $stmt = $conn->prepare("
        SELECT ev.id_evento, ev.nome_evento, ev.data_evento, ev.horario_evento,
               ev.status_evento, ev.id_criador,
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
        SELECT u.id_user, u.nome_user
        FROM Lista_Evento le
        INNER JOIN Usuario u ON u.id_user = le.id_user
        WHERE le.id_evento = ?
        ORDER BY u.nome_user ASC, u.id_user ASC
    ");
    $stmt->execute([$idEvento]);
    $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao carregar lista de presença: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar a lista de presença. Tente novamente.');
}

$nomeEsporte = trim((string) $evento['nome_esporte']);
$chaveEsporte = strtolower($nomeEsporte);
$jogadoresPorTime = $criteriosEquipe[$chaveEsporte] ?? null;

$totalConfirmados = count($participantes);
$totalDoisTimes = $jogadoresPorTime ? $jogadoresPorTime * 2 : null;
$faltam = $totalDoisTimes !== null ? max(0, $totalDoisTimes - $totalConfirmados) : null;
$excedentes = $totalDoisTimes !== null ? max(0, $totalConfirmados - $totalDoisTimes) : 0;
$percentual = $totalDoisTimes
    ? min(100, (int) round(($totalConfirmados / $totalDoisTimes) * 100))
    : 0;

$eventoCancelado = $evento['status_evento'] === 'cancelado';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de presença | <?= escaparListaPresenca($evento['nome_evento']) ?></title>
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
    <a class="eventos-voltar" href="detalhes-evento.php?id_evento=<?= (int) $idEvento ?>">← Voltar ao evento</a>

    <header class="presenca-topo">
        <div>
            <p class="eventos-marca"><?= escaparListaPresenca($nomeEsporte) ?></p>
            <h1>Lista de presença</h1>
            <p class="eventos-subtitulo"><?= escaparListaPresenca($evento['nome_evento']) ?></p>
        </div>
        <span class="presenca-status <?= $eventoCancelado ? 'presenca-status-cancelado' : '' ?>">
            <?= $eventoCancelado ? 'Evento cancelado' : 'Confirmações' ?>
        </span>
    </header>

    <section class="presenca-resumo" aria-label="Resumo da lista de presença">
        <article class="presenca-indicador">
            <span>Confirmados</span>
            <strong><?= $totalConfirmados ?></strong>
            <small>pessoa<?= $totalConfirmados === 1 ? '' : 's' ?></small>
        </article>

        <?php if ($jogadoresPorTime !== null): ?>
            <article class="presenca-indicador">
                <span>Por time</span>
                <strong><?= $jogadoresPorTime ?></strong>
                <small>jogadores</small>
            </article>

            <article class="presenca-indicador">
                <span>Para 2 times</span>
                <strong><?= $totalDoisTimes ?></strong>
                <small>jogadores</small>
            </article>

            <article class="presenca-indicador <?= $faltam === 0 ? 'presenca-indicador-completo' : '' ?>">
                <span><?= $faltam === 0 ? 'Situação' : 'Faltam' ?></span>
                <strong><?= $faltam === 0 ? '✓' : $faltam ?></strong>
                <small><?= $faltam === 0 ? 'dois times completos' : 'para completar dois times' ?></small>
            </article>
        <?php else: ?>
            <article class="presenca-indicador presenca-indicador-largo">
                <span>Formato</span>
                <strong>Individual</strong>
                <small>Esta modalidade não possui quantidade fixa de jogadores por time.</small>
            </article>
        <?php endif; ?>
    </section>

    <?php if ($jogadoresPorTime !== null): ?>
        <section class="presenca-progresso-card">
            <div class="presenca-progresso-cabecalho">
                <div>
                    <h2>Formação das equipes</h2>
                    <p><?= $totalConfirmados ?> de <?= $totalDoisTimes ?> confirmações para formar dois times completos.</p>
                </div>
                <strong><?= $percentual ?>%</strong>
            </div>

            <div class="presenca-barra" aria-label="<?= $percentual ?>% das vagas necessárias preenchidas">
                <span style="width: <?= $percentual ?>%"></span>
            </div>

            <?php if ($excedentes > 0): ?>
                <p class="presenca-extra">
                    Já existem <?= $excedentes ?> participante<?= $excedentes === 1 ? '' : 's' ?> além do necessário para dois times.
                </p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="presenca-lista-card">
        <div class="presenca-lista-cabecalho">
            <div>
                <h2>Integrantes confirmados</h2>
                <p>Presenças registradas na tabela Lista_Evento.</p>
            </div>
            <span><?= $totalConfirmados ?></span>
        </div>

        <?php if (!$participantes): ?>
            <div class="presenca-vazio">
                <strong>Ninguém confirmou presença ainda.</strong>
                <p>Quando alguém entrar no evento e confirmar participação, aparecerá nesta lista.</p>
            </div>
        <?php else: ?>
            <ol class="presenca-lista">
                <?php foreach ($participantes as $indice => $participante): ?>
                    <li>
                        <span class="presenca-numero"><?= $indice + 1 ?></span>
                        <div class="presenca-pessoa">
                            <strong><?= escaparListaPresenca($participante['nome_user']) ?></strong>
                            <small>
                                #<?= (int) $participante['id_user'] ?>
                                <?= (int) $participante['id_user'] === $idUsuario ? ' • você' : '' ?>
                                <?= (int) $participante['id_user'] === (int) $evento['id_criador'] ? ' • organizador' : '' ?>
                            </small>
                        </div>
                        <span class="presenca-confirmado">Confirmado</span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <section class="presenca-info-evento">
        <div>
            <span>Data</span>
            <strong><?= escaparListaPresenca(date('d/m/Y', strtotime($evento['data_evento']))) ?></strong>
        </div>
        <div>
            <span>Horário</span>
            <strong><?= escaparListaPresenca(substr($evento['horario_evento'], 0, 5)) ?></strong>
        </div>
        <div>
            <span>Local</span>
            <strong><?= escaparListaPresenca($evento['nome_local']) ?></strong>
            <small><?= escaparListaPresenca($evento['endereco_local']) ?></small>
        </div>
        <div>
            <span>Organizador</span>
            <strong><?= escaparListaPresenca($evento['criador'] ?? 'Usuário indisponível') ?></strong>
        </div>
    </section>
</main>

<?php
require __DIR__ . '/../includes/under-bar.php';
require __DIR__ . '/../includes/footer.php';
?>
