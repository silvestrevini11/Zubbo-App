<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../usuario/form-usuario.php');
    exit;
}

include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../../../config/database.php';

$id_usuario_logado = (int) $_SESSION['usuario']['id'];

$id_outro_usuario = (int) ($_GET['id'] ?? 0);

if ($id_outro_usuario <= 0 || $id_outro_usuario == $id_usuario_logado) {
    header('Location: chats.php');
    exit;
}


/*
    Colocamos sempre o menor ID primeiro.
    Assim:
    3 + 7
    e
    7 + 3

    representam a mesma conversa.
*/

$id_user_a = min($id_usuario_logado, $id_outro_usuario);
$id_user_b = max($id_usuario_logado, $id_outro_usuario);


/* ==========================================
   PEGAR DADOS DA OUTRA PESSOA
========================================== */

$stmtUsuario = $conn->prepare("
    SELECT nome_user, foto_user
    FROM Usuario
    WHERE id_user = ?
");

$stmtUsuario->execute([$id_outro_usuario]);

$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: chats.php');
    exit;
}


/* ==========================================
   PEGAR OU CRIAR CONVERSA
========================================== */

$stmtConversa = $conn->prepare("
    SELECT id_conversa
    FROM Conversa
    WHERE id_user_a = ?
      AND id_user_b = ?
");

$stmtConversa->execute([
    $id_user_a,
    $id_user_b
]);

$conversa = $stmtConversa->fetch(PDO::FETCH_ASSOC);


/* Se ainda não existe, cria */

if (!$conversa) {

    $stmtCriar = $conn->prepare("
        INSERT INTO Conversa (id_user_a, id_user_b)
        VALUES (?, ?)
    ");

    $stmtCriar->execute([
        $id_user_a,
        $id_user_b
    ]);

    $id_conversa = $conn->lastInsertId();

} else {

    $id_conversa = $conversa['id_conversa'];
}


/* ==========================================
   PEGAR MENSAGENS
========================================== */

$stmtMensagens = $conn->prepare("
    SELECT 
        id_remetente,
        mensagem,
        data_envio
    FROM Mensagem
    WHERE id_conversa = ?
    ORDER BY data_envio ASC
");

$stmtMensagens->execute([$id_conversa]);

$mensagens = $stmtMensagens->fetchAll(PDO::FETCH_ASSOC);


/* ==========================================
   FOTO
========================================== */

$fotoPerfil = !empty($usuario['foto_user'])
    ? '/-TCC-/' . $usuario['foto_user']
    : '/-TCC-/public/imagem/blank.png';

?>

<section class="chat-container">


    <!-- =====================================
         CABEÇALHO
    ====================================== -->

    <header class="chat-header">

        <a href="chats.php" class="chat-voltar">
            ←
        </a>

        <img
            src="<?= htmlspecialchars($fotoPerfil) ?>"
            alt="Foto de perfil"
            class="chat-foto"
        >

        <h2 class="chat-nome">
            <?= htmlspecialchars($usuario['nome_user']) ?>
        </h2>

    </header>


    <!-- =====================================
         MENSAGENS
    ====================================== -->

    <div class="chat-mensagens">

        <?php foreach ($mensagens as $msg): ?>

            <?php
                $minhaMensagem =
                    (int) $msg['id_remetente'] === $id_usuario_logado;
            ?>

            <div class="
                chat-mensagem
                <?= $minhaMensagem ? 'minha' : 'outra' ?>
            ">

                <span>
                    <?= nl2br(htmlspecialchars($msg['mensagem'])) ?>
                </span>

            </div>

        <?php endforeach; ?>

    </div>


    <!-- =====================================
         ENVIAR MENSAGEM
    ====================================== -->

    <form
        action="enviar-mensagem.php"
        method="POST"
        class="chat-form"
    >

        <input
            type="hidden"
            name="id_conversa"
            value="<?= $id_conversa ?>"
        >

        <input
            type="text"
            name="mensagem"
            class="chat-input"
            placeholder="Digite uma mensagem..."
            autocomplete="off"
            required
        >

        <button
            type="submit"
            class="chat-enviar"
        >
            ➤
        </button>

    </form>

</section>


<?php
include __DIR__ . '/../../views/includes/footer.php';
?>