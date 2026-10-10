<?php

session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_user = (int) $_SESSION['usuario']['id'];


/*
 * Busca todos os amigos do usuário
 */
$stmt = $conn->prepare("
    SELECT
        u.id_user,
        u.nome_user,
        u.foto_user
    FROM Amizade a

    INNER JOIN Usuario u
        ON u.id_user =
            CASE
                WHEN a.id_user_1 = ? THEN a.id_user_2
                ELSE a.id_user_1
            END

    WHERE a.id_user_1 = ?
       OR a.id_user_2 = ?

    ORDER BY u.nome_user
");

$stmt->execute([
    $id_user,
    $id_user,
    $id_user
]);

$amigos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/head.php';

?>

<section class="amigos-container">

<button
        class="btn-voltar"
        onclick="window.location.href='perfil.php'"
    >
        ←
    </button>

    <h1 class="amigos-titulo">
        Meus Amigos
    </h1>

    <?php if (empty($amigos)): ?>

        <p>
            Você ainda não possui amigos.
        </p>

    <?php endif; ?>


    <div class="amigos-lista">

        <?php foreach ($amigos as $amigo): ?>

            <?php

                $foto = !empty($amigo['foto_user'])
                    ? zubbo_url('/' . ltrim((string) $amigo['foto_user'], '/'))
                    : zubbo_url('/public/imagem/blank.png');

            ?>

            <a
                href="perfil-ver.php?id=<?= $amigo['id_user'] ?>"
                class="chat-item"
            >

                <img
                    src="<?= htmlspecialchars($foto) ?>"
                    alt="Foto de perfil"
                    class="chat-item-foto"
                >

                <div class="chat-item-info">

                    <strong>
                        <?= htmlspecialchars($amigo['nome_user']) ?>
                    </strong>

                </div>

            </a>

            <hr class="perfil-hr">

        <?php endforeach; ?>

    </div>

</section>


<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>