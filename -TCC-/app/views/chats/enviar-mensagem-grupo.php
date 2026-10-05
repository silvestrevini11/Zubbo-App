<?php
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: chats-grupos.php');
    exit;
}

zubbo_require_csrf();

$idUsuario = (int) $_SESSION['usuario']['id'];
$idConversa = filter_var($_POST['id_conversa'] ?? null, FILTER_VALIDATE_INT);
$mensagem = trim((string) ($_POST['mensagem'] ?? ''));

if (!$idConversa || $idConversa < 1 || $mensagem === '' || mb_strlen($mensagem) > 2000) {
    header('Location: chats-grupos.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT 1
    FROM Grupo g
    INNER JOIN Conversa c ON c.id_conversa = g.id_conversa AND c.tipo_conversa = 'grupo'
    INNER JOIN Participantes_Conversa pc ON pc.id_conversa = g.id_conversa
    WHERE g.id_conversa = ? AND pc.id_user = ?
    LIMIT 1
");
$stmt->execute([$idConversa, $idUsuario]);

if (!$stmt->fetchColumn()) {
    http_response_code(403);
    exit('Você não participa desta conversa.');
}

$stmt = $conn->prepare(
    'INSERT INTO Mensagem (id_conversa, id_remetente, mensagem) VALUES (?, ?, ?)'
);
$stmt->execute([$idConversa, $idUsuario, $mensagem]);

header('Location: chat-grupo.php?id_conversa=' . $idConversa, true, 303);
exit;
