<?php

session_start();

if (
    !isset($_SESSION['cadastro_pendente']) ||
    !isset($_SESSION['email_verificado'])
) {
    header('Location: cadastro.php');
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

                <?php
                    $nome = $esporte['nome_esporte'];

                    $classeEsporte = match ($nome) {
                        'Futebol'  => 'esporte-futebol',
                        'Basquete' => 'esporte-basquete',
                        'Vôlei'    => 'esporte-volei',
                        'Corrida'  => 'esporte-corrida',
                        'Futsal'   => 'esporte-futsal',
                        'Handebol' => 'esporte-handebol',
                        default    => '',
                    };
                ?>

                <label class="esporte-card <?= htmlspecialchars($classeEsporte) ?>">

                    <input
                        type="checkbox"
                        name="esportes[]"
                        value="<?= (int) $esporte['id_esporte'] ?>"
                    >

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
