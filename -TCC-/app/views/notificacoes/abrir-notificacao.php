<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login/login.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];

$id_notificacao =
    (int) ($_GET['id'] ?? 0);

if ($id_notificacao <= 0) {
    header('Location: notificacoes.php');
    exit;
}


/* ==========================================
   PEGAR NOTIFICAÇÃO
========================================== */

$stmt = $conn->prepare("
    SELECT
        id_notificacao,
        id_conversa,
        id_remetente
    FROM Notificacao
    WHERE id_notificacao = ?
      AND id_destinatario = ?
");

$stmt->execute([
    $id_notificacao,
    $id_usuario
]);

$notificacao = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$notificacao) {
    header('Location: notificacoes.php');
    exit;
}


/* ==========================================
   MARCAR COMO LIDA
========================================== */

$stmt = $conn->prepare("
    UPDATE Notificacao
    SET lida = TRUE
    WHERE id_notificacao = ?
      AND id_destinatario = ?
");

$stmt->execute([
    $id_notificacao,
    $id_usuario
]);


/* ==========================================
   ABRIR CONVERSA
========================================== */

$id_remetente =
    (int) $notificacao['id_remetente'];

header(
    'Location: ../chats/chats-conversas.php?id='
    . $id_remetente
);

exit;