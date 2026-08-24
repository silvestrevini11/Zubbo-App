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

            <?php foreach ($esportes as $esporte): ?>

                <label class="esporte-card">

                    <input
                        type="checkbox"
                        name="esportes[]"
                        value="<?= $esporte['id_esporte'] ?>"
                    >

                    <span>
                        <?= htmlspecialchars($esporte['nome_esporte']) ?>
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