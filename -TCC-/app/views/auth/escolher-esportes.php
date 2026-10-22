<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (
    !isset($_SESSION['cadastro_pendente']) ||
    !isset($_SESSION['email_verificado'])
) {
    header('Location: cadastro.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

$stmt = $conn->query(
    'SELECT id_esporte, nome_esporte, imagem_esporte
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
        <?= zubbo_csrf_input() ?>

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

                    <?php if (!empty($esporte['imagem_esporte'])): ?>
                      <img class="esporte-imagem-upload" src="<?= htmlspecialchars(zubbo_url('/'.ltrim((string)$esporte['imagem_esporte'],'/')), ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
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
