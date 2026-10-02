<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (!isset($_SESSION['cadastro_pendente'], $_SESSION['email_verificado'])) {
    header('Location: cadastro.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
zubbo_csrf_token();

$esportes = $conn->query(
    'SELECT id_esporte, nome_esporte FROM Esporte ORDER BY nome_esporte'
)->fetchAll(PDO::FETCH_ASSOC);

$imagensEsportes = [
    'Basquete' => 'basquete.png',
    'Futebol' => 'futebol.png',
    'Futsal' => 'futsal.png',
    'Handebol' => 'handebol.png',
    'Vôlei' => 'volei.png',
    'Corrida' => 'corrida.png',
];

include __DIR__ . '/../includes/head.php';
?>
<main class="esportes-container">
    <h1>Quais esportes você pratica?</h1>
    <p>Escolha um ou mais esportes.</p>

    <form action="salvar-esportes.php" method="post">
        <?= zubbo_csrf_input() ?>
        <div class="esportes-grid">
            <?php foreach ($esportes as $esporte): ?>
                <?php
                    $nome = $esporte['nome_esporte'];
                    $imagem = $imagensEsportes[$nome] ?? null;
                ?>
                <label class="esporte-card">
                    <input type="checkbox" name="esportes[]" value="<?= (int) $esporte['id_esporte'] ?>">
                    <?php if ($imagem): ?>
                        <span class="esporte-emoji">
                            <img
                                class="esporte-img"
                                src="/-TCC-/public/imagem/<?= htmlspecialchars($imagem, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </span>
                    <?php endif; ?>
                    <span class="esporte-nome"><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn-continuar">Continuar</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
