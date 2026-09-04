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
   BUSCAR CONVERSAS
========================================== */

$stmt = $conn->prepare("
    SELECT
        c.id_conversa,

        CASE
            WHEN c.id_user_a = :id_user
                THEN c.id_user_b
            ELSE c.id_user_a
        END AS id_outro_usuario,

        u.nome_user,
        u.foto_user,

        m.mensagem AS ultima_mensagem,
        m.data_envio

    FROM Conversa c

    INNER JOIN Usuario u
        ON u.id_user =
            CASE
                WHEN c.id_user_a = :id_user2
                    THEN c.id_user_b
                ELSE c.id_user_a
            END

    LEFT JOIN Mensagem m
        ON m.id_mensagem = (
            SELECT MAX(m2.id_mensagem)
            FROM Mensagem m2
            WHERE m2.id_conversa = c.id_conversa
        )

    WHERE c.id_user_a = :id_user3
       OR c.id_user_b = :id_user4

    ORDER BY
        COALESCE(m.data_envio, c.data_criacao) DESC
");

$stmt->execute([
    ':id_user'  => $id_usuario,
    ':id_user2' => $id_usuario,
    ':id_user3' => $id_usuario,
    ':id_user4' => $id_usuario
]);

$conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<section class="chats-container">
    <h1 style="" class="chats-titulo">
        Conversas
    </h1>

    <div class="chats-options">
        
    </div>

    <div class="seletor-tipo">
    <span class="seletor-indicador"></span>

    <button class="opcao ativa" data-tipo="privados" onclick="window.location.href='chats.php'">
        Privados
    </button>

    <button class="opcao" data-tipo="grupos" onclick="window.location.href='chats-grupos.php'">
        Grupos
    </button>

    <button class="opcao" data-tipo="comunidade" onclick="window.location.href='chats-comunidade.php'">
        Comunidade
    </button>
</div>

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
            <hr class="perfil-hr">
        <?php endforeach; ?>

    </div>



<script src="/../-TCC-/public/js/conversas.js"></script>

</section>



<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>