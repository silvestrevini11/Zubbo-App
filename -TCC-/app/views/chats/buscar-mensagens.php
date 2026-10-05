<?php
require_once __DIR__ . '/../../middleware/auth.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$idConversa = filter_var($_GET['id_conversa'] ?? null, FILTER_VALIDATE_INT);

if (!$idConversa || $idConversa < 1) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare(
    'SELECT 1
     FROM Participantes_Conversa
     WHERE id_conversa = ? AND id_user = ?
     LIMIT 1'
);
$stmt->execute([$idConversa, $idUsuario]);

if (!$stmt->fetchColumn()) {
    http_response_code(403);
    exit;
}

$stmt = $conn->prepare(
    'SELECT m.id_mensagem, m.id_remetente, m.mensagem, m.data_envio, u.nome_user
     FROM Mensagem m
     INNER JOIN Usuario u ON u.id_user = m.id_remetente
     WHERE m.id_conversa = ?
     ORDER BY m.data_envio ASC, m.id_mensagem ASC'
);
$stmt->execute([$idConversa]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($mensagens as &$msg) {
    $msg['minha'] = (int) $msg['id_remetente'] === $idUsuario;
}
unset($msg);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($mensagens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
