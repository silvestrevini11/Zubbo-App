<?php
require_once __DIR__ . '/../../middleware/auth.php';

$id_usuario_logado = (int) $_SESSION['usuario']['id'];

$id_outro_usuario = (int) ($_GET['id'] ?? 0);

require_once __DIR__ . '/../../services/AmizadeService.php';

if (!zubbo_amizade_aceita($conn, $id_usuario_logado, $id_outro_usuario)) {
    // Acesso por URL direta também precisa respeitar a amizade aceita.
    if ($id_outro_usuario > 0 && $id_outro_usuario !== $id_usuario_logado) {
        header('Location: ../perfil/perfil-ver.php?id=' . $id_outro_usuario . '&chat=amizade', true, 303);
    } else {
        header('Location: chats.php', true, 303);
    }
    exit;
}

$stmtBloqueio = $conn->prepare("SELECT 1 FROM Bloqueio_Usuario
 WHERE (id_bloqueador=? AND id_bloqueado=?) OR (id_bloqueador=? AND id_bloqueado=?) LIMIT 1");
$stmtBloqueio->execute([$id_usuario_logado,$id_outro_usuario,$id_outro_usuario,$id_usuario_logado]);
if ($stmtBloqueio->fetchColumn()) {
    http_response_code(403); exit('Conversa indisponível devido ao bloqueio.');
}


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
    WHERE id_user = ? AND status_user = 'ativo'
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


/* Criação de conversa privada protegida por locks para não duplicar com dois cliques. */
if (!$conversa) {
    try {
        $conn->beginTransaction();

        // Bloqueios consistentes na mesma ordem evitam deadlock para o mesmo par.
        $ids = [$id_usuario_logado, $id_outro_usuario];
        sort($ids, SORT_NUMERIC);
        $lock = $conn->prepare("SELECT id_user, status_user FROM Usuario
            WHERE id_user IN (?, ?) ORDER BY id_user FOR UPDATE");
        $lock->execute($ids);
        $usuariosAtivos = $lock->fetchAll(PDO::FETCH_ASSOC);
        if (count($usuariosAtivos) !== 2 ||
            $usuariosAtivos[0]['status_user'] !== 'ativo' ||
            $usuariosAtivos[1]['status_user'] !== 'ativo') {
            throw new RuntimeException('Usuário indisponível.');
        }

        // Reconsultar depois do lock: outra requisição pode ter criado a conversa.
        $stmtConversa->execute([$id_usuario_logado, $id_outro_usuario]);
        $conversa = $stmtConversa->fetch(PDO::FETCH_ASSOC);
        if (!$conversa) {
            $conn->prepare("INSERT INTO Conversa (tipo_conversa) VALUES ('privado')")->execute();
            $id_conversa = (int)$conn->lastInsertId();
            $inserir = $conn->prepare("INSERT INTO Participantes_Conversa (id_user,id_conversa) VALUES (?,?)");
            $inserir->execute([$id_usuario_logado,$id_conversa]);
            $inserir->execute([$id_outro_usuario,$id_conversa]);
        } else {
            $id_conversa = (int)$conversa['id_conversa'];
        }
        $conn->commit();
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Falha ao abrir conversa privada: '.get_class($e));
        http_response_code(503);
        exit('Não foi possível abrir a conversa agora.');
    }
} else {
    $id_conversa = (int)$conversa['id_conversa'];
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

$conn->prepare("UPDATE Notificacao SET lida = TRUE, data_leitura = COALESCE(data_leitura, NOW()) WHERE id_destinatario = ? AND id_conversa = ? AND tipo = 'mensagem' AND lida = FALSE")
    ->execute([$id_usuario_logado, $id_conversa]);


/* ==========================================
   FOTO
========================================== */

$fotoPerfil = !empty($usuario['foto_user'])
    ? zubbo_url('/' . ltrim((string) $usuario['foto_user'], '/'))
    : zubbo_url('/public/imagem/blank.png');

include __DIR__ . '/../includes/head.php';
?>

<section class="chat-container">


    <!-- =====================================
         CABEÇALHO
    ====================================== -->

    <header class="chat-header">

        <a onclick="window.location.href='chats.php'" class="chat-voltar">
         <span class="seta-esquerda">&#10140;</span>
        </a>

       <a href="../perfil/perfil-ver.php?id=<?= $id_outro_usuario ?>">
            <img
                src="<?= htmlspecialchars($fotoPerfil) ?>"
                alt="Foto de perfil"
                class="chat-foto"
            >
        </a>

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
        <?= zubbo_csrf_input() ?>

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

setInterval(() => { if (!document.hidden) atualizarChat(); }, 4000);

</script>

<?php
include __DIR__ . '/../../views/includes/footer.php';
?>