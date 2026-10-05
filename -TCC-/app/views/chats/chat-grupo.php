<?php
require_once __DIR__ . '/../../middleware/auth.php';
include __DIR__ . '/../includes/head.php';

$id_usuario = (int)$_SESSION['usuario']['id'];
$id_conversa = (int)($_GET['id_conversa'] ?? 0);

$stmt = $conn->prepare("
    SELECT g.*, COUNT(pc2.id_user) AS total_participantes
    FROM Grupo g
    INNER JOIN Conversa c ON c.id_conversa = g.id_conversa AND c.tipo_conversa = 'grupo'
    INNER JOIN Participantes_Conversa pc ON pc.id_conversa = g.id_conversa AND pc.id_user = ?
    LEFT JOIN Participantes_Conversa pc2 ON pc2.id_conversa = g.id_conversa
    WHERE g.id_conversa = ?
    GROUP BY g.id_grupo
");
$stmt->execute([$id_usuario, $id_conversa]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: chats-grupos.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT m.id_remetente, m.mensagem, m.data_envio, u.nome_user
    FROM Mensagem m
    INNER JOIN Usuario u ON u.id_user = m.id_remetente
    WHERE m.id_conversa = ?
    ORDER BY m.data_envio ASC
");
$stmt->execute([$id_conversa]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$foto = $grupo['foto_grupo']
    ? zubbo_url('/' . ltrim((string) $grupo['foto_grupo'], '/'))
    : zubbo_url('/public/imagem/blank.png');
?>
<section class="chat-container">
    <header class="chat-header">
        <a onclick="location.href='chats-grupos.php'" class="chat-voltar"><span class="seta-esquerda">&#10140;</span></a>
        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do grupo" class="chat-foto">
        <div class="grupo-chat-header-info">
            <h2 class="chat-nome"><?= htmlspecialchars($grupo['nome_grupo']) ?></h2>
            <span><?= (int)$grupo['total_participantes'] ?> participantes</span>
        </div>

        <?php if ((int)$grupo['id_criador'] === $id_usuario): ?>
            <button
                type="button"
                class="grupo-btn-editar"
                onclick="location.href='editar-grupo.php?id_grupo=<?= (int)$grupo['id_grupo'] ?>'"
                aria-label="Editar grupo"
                title="Editar grupo"
            >⚙</button>
        <?php endif; ?>
    </header>

    <div class="chat-mensagens" id="chat-mensagens"  data-conversa="<?= $id_conversa ?>">
        <?php foreach ($mensagens as $msg): ?>
            <?php $minha = (int)$msg['id_remetente'] === $id_usuario; ?>
            <div class="chat-mensagem <?= $minha ? 'minha' : 'outra' ?>">
                <?php if (!$minha): ?><small class="grupo-msg-remetente"><?= htmlspecialchars($msg['nome_user']) ?></small><?php endif; ?>
                <span><?= nl2br(htmlspecialchars($msg['mensagem'])) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <form action="enviar-mensagem-grupo.php" method="POST" class="chat-form">
        <?= zubbo_csrf_input() ?>
        <input type="hidden" name="id_conversa" value="<?= $id_conversa ?>">
        <input type="text" name="mensagem" class="chat-input" placeholder="Digite uma mensagem..." autocomplete="off" required>
        <button type="submit" class="chat-enviar">➤</button>
    </form>
</section>
<script>
const box = document.getElementById('chat-mensagens');
if (box) box.scrollTop = box.scrollHeight;
</script>

<script>
const chatMensagens = document.getElementById('chat-mensagens');

if (chatMensagens) {

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
                Só atualiza quando a quantidade
                de mensagens mudar.
            */

            if (mensagens.length !== quantidadeMensagens) {

                chatMensagens.innerHTML = '';


                mensagens.forEach(msg => {

                    const div = document.createElement('div');

                    div.classList.add(
                        'chat-mensagem',
                        msg.minha ? 'minha' : 'outra'
                    );


                    /*
                        Em grupos, mostra o nome
                        de quem enviou a mensagem.
                    */

                    if (!msg.minha && msg.nome_user) {

                        const remetente =
                            document.createElement('small');

                        remetente.classList.add(
                            'grupo-msg-remetente'
                        );

                        remetente.textContent =
                            msg.nome_user;

                        div.appendChild(remetente);

                    }


                    const span =
                        document.createElement('span');

                    /*
                        Protege contra HTML malicioso.
                    */

                    span.textContent =
                        msg.mensagem;


                    div.appendChild(span);

                    chatMensagens.appendChild(div);

                });


                quantidadeMensagens =
                    mensagens.length;


                /*
                    Desce automaticamente
                    para a mensagem mais recente.
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
        Desce para a última mensagem
        quando abrir o grupo.
    */

    chatMensagens.scrollTop =
        chatMensagens.scrollHeight;


    /*
        Verifica novas mensagens
        a cada 1 segundo.
    */

    setInterval(
        atualizarChat,
        1000
    );

}
</script>

<?php include __DIR__ . '/../../views/includes/footer.php'; ?>