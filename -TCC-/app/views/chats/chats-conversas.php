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
    SELECT c.id_conversa
    FROM Conversa c

    INNER JOIN Participantes_Conversa pc1
        ON pc1.id_conversa = c.id_conversa
        AND pc1.id_user = ?

    INNER JOIN Participantes_Conversa pc2
        ON pc2.id_conversa = c.id_conversa
        AND pc2.id_user = ?

    WHERE c.tipo_conversa = 'privado'
");

$stmtConversa->execute([
    $id_usuario_logado,
    $id_outro_usuario
]);

$conversa = $stmtConversa->fetch(PDO::FETCH_ASSOC);


/* Se ainda não existe, cria */

if (!$conversa) {

    $stmtCriar = $conn->prepare("
        INSERT INTO Conversa (tipo_conversa)
        VALUES ('privado')
    ");

    $stmtCriar->execute();

    $id_conversa = $conn->lastInsertId();


    /* Adiciona o usuário logado */

    $stmtParticipante = $conn->prepare("
        INSERT INTO Participantes_Conversa
        (id_user, id_conversa)
        VALUES (?, ?)
    ");

    $stmtParticipante->execute([
        $id_usuario_logado,
        $id_conversa
    ]);


    /* Adiciona o outro usuário */

    $stmtParticipante->execute([
        $id_outro_usuario,
        $id_conversa
    ]);

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

        <a onclick="window.location.href='chats.php'" class="chat-voltar">
         <span class="seta-esquerda">&#10140;</span>
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

    <div
    class="chat-mensagens"
    id="chat-mensagens"
    data-conversa="<?= $id_conversa ?>"
>

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

<script>

const chatMensagens = document.getElementById('chat-mensagens');

const idConversa = chatMensagens.dataset.conversa;

let quantidadeMensagens = chatMensagens.children.length;


async function atualizarChat() {

    try {

        const resposta = await fetch(
            'buscar-mensagens.php?id_conversa=' + idConversa
        );

        if (!resposta.ok) {
            return;
        }

        const mensagens = await resposta.json();


        /*
            Só atualiza se apareceu
            alguma mensagem nova.
        */

        if (mensagens.length !== quantidadeMensagens) {

            chatMensagens.innerHTML = '';


            mensagens.forEach(msg => {

                const div = document.createElement('div');

                div.classList.add(
                    'chat-mensagem',
                    msg.minha ? 'minha' : 'outra'
                );


                const span = document.createElement('span');

                /*
                    textContent protege contra HTML malicioso.
                */

                span.textContent = msg.mensagem;


                div.appendChild(span);

                chatMensagens.appendChild(div);

            });


            quantidadeMensagens = mensagens.length;


            /*
                Desce automaticamente para
                a mensagem mais recente.
            */

            chatMensagens.scrollTop =
                chatMensagens.scrollHeight;

        }

    } catch (erro) {

        console.error(
            'Erro ao atualizar o chat:',
            erro
        );

    }

}


/*
    Verifica novas mensagens
    a cada 1 segundo.
*/

setInterval(atualizarChat, 1000);

</script>

<?php
include __DIR__ . '/../../views/includes/footer.php';
?>