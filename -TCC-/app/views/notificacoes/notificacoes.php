<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login/login.php');
    exit;
}

include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];


/* ==========================================
   BUSCAR NOTIFICAÇÕES
========================================== */

$stmt = $conn->prepare("
    SELECT
        n.id_notificacao,
        n.id_conversa,
        n.id_mensagem,
        n.tipo,
        n.lida,
        n.data_criacao,

        u.id_user AS id_remetente,
        u.nome_user,
        u.foto_user,

        m.mensagem

    FROM Notificacao n

    INNER JOIN Usuario u
        ON u.id_user = n.id_remetente

    LEFT JOIN Mensagem m
        ON m.id_mensagem = n.id_mensagem

    WHERE n.id_destinatario = ?

    ORDER BY n.data_criacao DESC
");

$stmt->execute([$id_usuario]);

$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<section
    class="notificacoes-container"
    style="padding-bottom: 80px;"

    >

    <button class="btn-voltar" onclick="window.location.href='../painel/painel-inicial.php'">
    ←  
    </button>

    <h1 style="padding-top: 40px;" class="notificacoes-titulo">
        Notificações
    </h1>


    <div class="notificacoes-lista">

        <?php if (empty($notificacoes)): ?>

            <p class="notificacoes-vazio">
                Você não possui notificações.
            </p>

        <?php endif; ?>


        <?php foreach ($notificacoes as $notificacao): ?>

            <?php

                $foto = !empty($notificacao['foto_user'])
                    ? '/-TCC-/' . $notificacao['foto_user']
                    : '/-TCC-/public/imagem/blank.png';

            ?>


            <div
    class="
        notificacao-item
        <?= !$notificacao['lida'] ? 'nao-lida' : '' ?>
    "
>

    <a
        href="abrir-notificacao.php?id=<?= (int) $notificacao['id_notificacao'] ?>"
        class="notificacao-link"
    >

        <img
            src="<?= htmlspecialchars($foto) ?>"
            alt=""
            class="notificacao-foto"
        >

        <div class="notificacao-info">

            <strong class="notificacao-nome-user">
                <?= htmlspecialchars($notificacao['nome_user']) ?>
            </strong>

            <span class="notificacao-env-men">

                <?php if ($notificacao['tipo'] === 'mensagem'): ?>

                    enviou uma mensagem

                <?php elseif ($notificacao['tipo'] === 'amizade'): ?>

                    enviou uma solicitação de amizade

                <?php elseif ($notificacao['tipo'] === 'amizade_aceita'): ?>

                    aceitou sua solicitação de amizade

                <?php endif; ?>

            </span>

            <p class="notificacao-mensagem">
                <?= htmlspecialchars($notificacao['mensagem'] ?? '') ?>
            </p>

        </div>

    </a>


    <?php if ($notificacao['tipo'] === 'amizade'): ?>

        <form action="aceitar-amizade.php" method="POST">

            <input
                type="hidden"
                name="id_remetente"
                value="<?= (int) $notificacao['id_remetente'] ?>"
            >

            <button type="submit">
                Aceitar
            </button>

        </form>

    <?php endif; ?>

</div>

        <?php endforeach; ?>

    </div>

</section>


<?php

include __DIR__ . '/../../views/includes/footer.php';

?>