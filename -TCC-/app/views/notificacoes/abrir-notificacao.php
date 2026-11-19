<?php
require_once __DIR__ . '/../../middleware/auth.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$idNotificacao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idNotificacao) {
    header('Location: notificacoes.php', true, 303);
    exit;
}
$stmt = $conn->prepare("SELECT n.id_conversa, n.id_evento, n.id_remetente, n.tipo, c.tipo_conversa
    FROM Notificacao n
    LEFT JOIN Conversa c ON c.id_conversa = n.id_conversa
    WHERE n.id_notificacao = ? AND n.id_destinatario = ?
    LIMIT 1");
$stmt->execute([$idNotificacao, $idUsuario]);
$notificacao = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$notificacao) {
    header('Location: notificacoes.php', true, 303);
    exit;
}
$conn->prepare("UPDATE Notificacao
    SET lida = TRUE, data_leitura = COALESCE(data_leitura, NOW())
    WHERE id_notificacao = ? AND id_destinatario = ?")
    ->execute([$idNotificacao, $idUsuario]);

if (in_array($notificacao['tipo'], ['convite_evento','vaga_solicitada','vaga_aprovada'], true)
    && (int)$notificacao['id_evento'] > 0) {
    header('Location: ../eventos/detalhes-evento.php?id_evento='.(int)$notificacao['id_evento'],true,303);
    exit;
}
if ($notificacao['tipo'] === 'grupo_adicionado' && (int)$notificacao['id_conversa'] > 0) {
    header('Location: ../chats/chat-grupo.php?id_conversa='.(int)$notificacao['id_conversa'], true, 303);
    exit;
}
if ($notificacao['tipo'] === 'amizade_aceita' && (int)$notificacao['id_remetente'] > 0) {
    header('Location: ../perfil/perfil-ver.php?id='.(int)$notificacao['id_remetente'], true, 303);
    exit;
}
if ($notificacao['tipo'] === 'mensagem' && $notificacao['tipo_conversa'] === 'grupo') {
    // O chat-grupo.php verifica a participação no grupo.
    header('Location: ../chats/chat-grupo.php?id_conversa='.(int)$notificacao['id_conversa'], true, 303);
    exit;
}
if ($notificacao['tipo'] === 'mensagem' && $notificacao['tipo_conversa'] === 'privado') {
    header('Location: ../chats/chats-conversas.php?id='.(int)$notificacao['id_remetente'], true, 303);
    exit;
}
header('Location: notificacoes.php', true, 303);
exit;
