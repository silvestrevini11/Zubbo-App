<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../usuario/form-usuario.php');
    exit;
}

include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];


/* ==========================================
   Busca Conversas
========================================== */

$stmt = $conn->prepare("
    SELECT
        c.id_conversa,
        c.tipo_conversa,
        c.nome_conversa,

        u.id_user AS id_outro_usuario,
        u.nome_user,
        u.foto_user,

        m.mensagem AS ultima_mensagem,
        m.data_envio

    FROM Conversa c

    INNER JOIN Participantes_Conversa pc_usuario
        ON pc_usuario.id_conversa = c.id_conversa
        AND pc_usuario.id_user = :id_usuario

    LEFT JOIN Participantes_Conversa pc_outro
        ON pc_outro.id_conversa = c.id_conversa
        AND pc_outro.id_user != :id_usuario2

    LEFT JOIN Usuario u
        ON u.id_user = pc_outro.id_user

    LEFT JOIN Mensagem m
        ON m.id_mensagem = (
            SELECT MAX(m2.id_mensagem)
            FROM Mensagem m2
            WHERE m2.id_conversa = c.id_conversa
        )

    ORDER BY
        COALESCE(m.data_envio, c.data_criacao) DESC
");

$stmt->execute([
    ':id_usuario'  => $id_usuario,
    ':id_usuario2' => $id_usuario
]);

$conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<section class="chats-container">

    <h1 class="chats-titulo">
        Conversas
    </h1>

    <div class="chats-lista">

        <?php if (empty($conversas)): ?>

            <p class="chats-vazio">
                Você ainda não possui conversas.
            </p>

        <?php endif; ?>


        <?php foreach ($conversas as $conversa): ?>

            <?php

            $foto = !empty($conversa['foto_user'])
                ? '/-TCC-/' . $conversa['foto_user']
                : '/-TCC-/public/imagem/blank.png';

            ?>

            <a
                href="chats-conversas.php?id=<?= $conversa['id_outro_usuario'] ?>"
                class="chat-item"
            >

                <img
                    src="<?= htmlspecialchars($foto) ?>"
                    alt="Foto de perfil"
                    class="chat-item-foto"
                >

                <div class="chat-item-info">

                    <strong>
                        <?= htmlspecialchars($conversa['nome_user']) ?>
                    </strong>

                    <span>
                        <?=
                            !empty($conversa['ultima_mensagem'])
                                ? htmlspecialchars($conversa['ultima_mensagem'])
                                : 'Nova conversa'
                        ?>
                    </span>

                </div>

            </a>

        <?php endforeach; ?>

    </div>

</section>

<?php

include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';

?>