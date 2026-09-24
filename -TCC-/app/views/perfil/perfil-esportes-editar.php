<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_user = (int) $_SESSION['usuario']['id'];

$stmt = $conn->query('SELECT id_esporte, nome_esporte FROM Esporte ORDER BY nome_esporte');
$esportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtSelecionados = $conn->prepare('SELECT id_esporte FROM Usuario_Esporte WHERE id_user = ?');
$stmtSelecionados->execute([$id_user]);
$selecionados = array_map('intval', $stmtSelecionados->fetchAll(PDO::FETCH_COLUMN));

$imagensEsportes = [
    'Basquete' => 'basquete.png',
    'Futebol'  => 'futebol.png',
    'Futsal'   => 'futsal.png',
    'Handebol' => 'handebol.png',
    'Vôlei'    => 'volei.png',
    'Corrida'  => 'corrida.png',
];

$erro = $_GET['erro'] ?? '';
include __DIR__ . '/../includes/head.php';
?>

<main class="editar-esportes-container">
    <header class="editar-esportes-topo">
        <a class="editar-dados-voltar" href="perfil-editar.php" aria-label="Voltar para alterar dados">←</a>
        <div>
            <p>PERFIL</p>
            <h1>Alterar esportes</h1>
        </div>
    </header>

    <p class="editar-esportes-descricao">Marque os esportes que você pratica. Os que já estavam no seu perfil aparecem selecionados.</p>

    <?php if ($erro === 'nenhum'): ?>
        <div class="editar-dados-alerta editar-dados-erro" role="alert">Selecione pelo menos um esporte.</div>
    <?php elseif ($erro !== ''): ?>
        <div class="editar-dados-alerta editar-dados-erro" role="alert">Não foi possível salvar os esportes. Tente novamente.</div>
    <?php endif; ?>

    <form action="salvar-esportes-perfil.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="editar-esportes-grid">
            <?php foreach ($esportes as $esporte): ?>
                <?php
                    $id = (int) $esporte['id_esporte'];
                    $nome = $esporte['nome_esporte'];
                    $imagem = $imagensEsportes[$nome] ?? null;
                    $checked = in_array($id, $selecionados, true);
                ?>

                <label class="editar-esportes-card">
                    <input
                        type="checkbox"
                        name="esportes[]"
                        value="<?= $id ?>"
                        <?= $checked ? 'checked' : '' ?>
                    >

                    <span class="editar-esportes-icone" aria-hidden="true">
                        <?php if ($imagem): ?>
                            <img src="/../-TCC-/public/imagem/<?= htmlspecialchars($imagem) ?>" alt="">
                        <?php else: ?>
                            ★
                        <?php endif; ?>
                    </span>

                    <span class="editar-esportes-nome"><?= htmlspecialchars($nome) ?></span>
                    <span class="editar-esportes-check" aria-hidden="true">✓</span>
                </label>
            <?php endforeach; ?>
        </div>

        <button class="editar-esportes-salvar" type="submit">Salvar esportes</button>
    </form>
</main>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
