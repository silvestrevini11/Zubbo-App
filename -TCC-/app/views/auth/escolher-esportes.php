
<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: form-usuario.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

$stmt = $conn->query(
    'SELECT id_esporte, nome_esporte
     FROM Esporte
     ORDER BY nome_esporte'
);

$esportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/head.php';
?>

<main class="esportes-container">

    <h1>Quais esportes você pratica?</h1>

    <p>Escolha um ou mais esportes.</p>

    <form action="salvar-esportes.php" method="POST">

        <div class="esportes-grid">

 <?php
$imagensEsportes = [
    'Basquete' => 'basquete.png',
    'Futebol'  => 'futebol.png',
    'Futsal'   => 'futsal.png',
    'Handebol' => 'handebol.png',
    'Vôlei'    => 'volei.png',
    'Corrida'  => 'corrida.png',
];
?>

<?php foreach ($esportes as $esporte): ?>

    <?php
        $nome = $esporte['nome_esporte'];
        $imagem = $imagensEsportes[$nome] ?? null;
    ?>

    <label class="esporte-card">

        <input
            type="checkbox"
            name="esportes[]"
            value="<?= $esporte['id_esporte'] ?>"
        >

        <?php if ($imagem): ?>
            <span class="esporte-emoji">
                <img class="esporte-img"
                    src="/-tcc-/public/imagem/<?= htmlspecialchars($imagem) ?>"
                    alt="<?= htmlspecialchars($nome) ?>"
                >
            </span>
        <?php endif; ?>

        <span class="esporte-nome">
            <?= htmlspecialchars($nome) ?>
        </span>

    </label>

<?php endforeach; ?>



        </div>

        <button type="submit" class="btn-continuar">
            Continuar
        </button>

    </form>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

