<?php
require_once __DIR__ . '/../../middleware/auth.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$idNotificacao = (int) ($_GET['id'] ?? 0);

if ($idNotificacao <= 0) {
    header('Location: notificacoes.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT id_notificacao, id_conversa, id_remetente
     FROM Notificacao
     WHERE id_notificacao = ? AND id_destinatario = ?
     LIMIT 1'
);
$stmt->execute([$idNotificacao, $idUsuario]);
$notificacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notificacao) {
    header('Location: notificacoes.php');
    exit;
}

$conn->prepare(
    'UPDATE Notificacao SET lida = TRUE WHERE id_notificacao = ? AND id_destinatario = ?'
)->execute([$idNotificacao, $idUsuario]);

$idRemetente = (int) $notificacao['id_remetente'];
header('Location: ../chats/chats-conversas.php?id=' . $idRemetente);
exit;
