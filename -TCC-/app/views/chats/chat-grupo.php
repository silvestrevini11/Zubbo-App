<?php
session_start();
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}
include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../../../config/database.php';

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

$foto = $grupo['foto_grupo'] ? '/-TCC-/'.$grupo['foto_grupo'] : '/-TCC-/public/imagem/blank.png';
?>
<section class="chat-container">
    <header class="chat-header">
        <a onclick="location.href='chats-grupos.php'" class="chat-voltar"><span class="seta-esquerda">&#10140;</span></a>
        <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do grupo" class="chat-foto">
        <div class="grupo-chat-header-info">
            <h2 class="chat-nome"><?= htmlspecialchars($grupo['nome_grupo']) ?></h2>
            <span><?= (int)$grupo['total_participantes'] ?> participantes</span>
        </div>
    </header>

    <div class="chat-mensagens" id="chat-mensagens">
        <?php foreach ($mensagens as $msg): ?>
            <?php $minha = (int)$msg['id_remetente'] === $id_usuario; ?>
            <div class="chat-mensagem <?= $minha ? 'minha' : 'outra' ?>">
                <?php if (!$minha): ?><small class="grupo-msg-remetente"><?= htmlspecialchars($msg['nome_user']) ?></small><?php endif; ?>
                <span><?= nl2br(htmlspecialchars($msg['mensagem'])) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <form action="enviar-mensagem-grupo.php" method="POST" class="chat-form">
        <input type="hidden" name="id_conversa" value="<?= $id_conversa ?>">
        <input type="text" name="mensagem" class="chat-input" placeholder="Digite uma mensagem..." autocomplete="off" required>
        <button type="submit" class="chat-enviar">➤</button>
    </form>
</section>
<script>
const box = document.getElementById('chat-mensagens');
if (box) box.scrollTop = box.scrollHeight;
</script>
<?php include __DIR__ . '/../../views/includes/footer.php'; ?>
