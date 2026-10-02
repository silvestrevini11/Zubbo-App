<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';

$eventos = [];
$erro = false;
try {
    $stmt = $conn->query("
        SELECT ev.id_evento, ev.nome_evento, ev.data_evento, ev.horario_evento,
               ev.status_evento, e.nome_esporte, l.nome_local, l.endereco_local,
               u.nome_user AS criador
        FROM Evento ev
        INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
        INNER JOIN LocalEsp l ON l.id_local = ev.id_local
        LEFT JOIN Usuario u ON u.id_user = ev.id_criador
        WHERE ev.status_evento <> 'removido'
        ORDER BY ev.data_evento DESC, ev.horario_evento DESC, ev.id_evento DESC
    ");
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao listar eventos: ' . $e->getMessage());
    $erro = true;
}
function escaparEvento($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos | Zubbo</title>
    <script>
        if (localStorage.getItem('zubbo-tema') === 'escuro') {
            document.documentElement.classList.add('tema-escuro');
        }
    </script>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/eventos.css">
</head>
<body>
<main class="eventos-container">
    <header class="eventos-topo">
        <div>
            <p class="eventos-marca">ZUBBO</p>
            <h1>Eventos</h1>
            <p class="eventos-subtitulo">Encontre a galera e entre em jogo.</p>
        </div>
        <a class="eventos-criar" href="criar-evento.php">+ Criar evento</a>
    </header>

    <?php if ($erro): ?>
        <section class="eventos-vazio" role="alert">
            <h2>Não foi possível carregar os eventos</h2>
            <p>Tente atualizar a página em alguns instantes.</p>
            <a class="eventos-criar" href="eventos.php">Tentar novamente</a>
        </section>
    <?php elseif (!$eventos): ?>
        <section class="eventos-vazio">
            <span class="eventos-vazio-icone" aria-hidden="true">🏆</span>
            <h2>O próximo encontro começa com você</h2>
            <p>Ainda não há eventos cadastrados. Crie o primeiro para reunir a galera!</p>
            <a class="eventos-criar" href="criar-evento.php">Criar meu primeiro evento</a>
        </section>
    <?php else: ?>
        <p class="eventos-contagem"><?= count($eventos) ?> evento(s) cadastrado(s)</p>
        <section class="eventos-grid" aria-label="Eventos cadastrados">
            <?php foreach ($eventos as $evento): ?>
                <article class="eventos-card">
                    <div class="eventos-card-topo">
                        <span class="eventos-esporte"><?= escaparEvento($evento['nome_esporte']) ?></span>
                        <span class="eventos-status <?= $evento['status_evento'] === 'cancelado' ? 'eventos-status-cancelado' : '' ?>">
                            <?= $evento['status_evento'] === 'cancelado' ? 'Cancelado' : 'Ativo' ?>
                        </span>
                    </div>
                    <h2><?= escaparEvento($evento['nome_evento']) ?></h2>
                    <dl class="eventos-dados">
                        <div>
                            <dt>Data e horário</dt>
                            <dd>
                                <time datetime="<?= escaparEvento($evento['data_evento']) ?>"><?= escaparEvento(date('d/m/Y', strtotime($evento['data_evento']))) ?></time>
                                às <?= escaparEvento(substr($evento['horario_evento'], 0, 5)) ?>
                            </dd>
                        </div>
                        <div>
                            <dt>Local</dt>
                            <dd><?= escaparEvento($evento['nome_local']) ?></dd>
                            <dd class="eventos-endereco"><?= escaparEvento($evento['endereco_local']) ?></dd>
                        </div>
                        <div>
                            <dt>Organizado por</dt>
                            <dd><?= escaparEvento($evento['criador'] ?? 'Usuário indisponível') ?></dd>
                        </div>
                    </dl>
                    <a class="eventos-criar evento-abrir" href="detalhes-evento.php?id_evento=<?= (int) $evento['id_evento'] ?>">Entrar no evento →</a>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php
require __DIR__ . '/../includes/under-bar.php';
require __DIR__ . '/../includes/footer.php';
?>
