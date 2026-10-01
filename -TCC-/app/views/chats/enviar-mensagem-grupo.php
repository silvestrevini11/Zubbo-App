<?php
session_start();
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: chats-grupos.php');
    exit;
}
include __DIR__ . '/../../../config/database.php';

$id_usuario = (int)$_SESSION['usuario']['id'];
$id_conversa = (int)($_POST['id_conversa'] ?? 0);
$mensagem = trim($_POST['mensagem'] ?? '');

if ($id_conversa <= 0 || $mensagem === '') {
    header('Location: chats-grupos.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT g.id_grupo
    FROM Grupo g
    INNER JOIN Conversa c ON c.id_conversa = g.id_conversa AND c.tipo_conversa = 'grupo'
    INNER JOIN Participantes_Conversa pc ON pc.id_conversa = g.id_conversa AND pc.id_user = ?
    WHERE g.id_conversa = ?
");
$stmt->execute([$id_usuario, $id_conversa]);

if (!$stmt->fetch()) {
    header('Location: chats-grupos.php');
    exit;
}

$stmt = $conn->prepare("INSERT INTO Mensagem (id_conversa, id_remetente, mensagem) VALUES (?, ?, ?)");
$stmt->execute([$id_conversa, $id_usuario, $mensagem]);

header('Location: chat-grupo.php?id_conversa='.$id_conversa);
exit;