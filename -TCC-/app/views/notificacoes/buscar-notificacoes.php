<?php

require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];


$stmt = $conn->prepare("
    SELECT COUNT(*) AS quantidade,
           COUNT(DISTINCT CASE WHEN tipo = 'mensagem' AND id_conversa IS NOT NULL THEN id_conversa END) AS conversas_nao_lidas
    FROM Notificacao
    WHERE id_destinatario = ?
      AND lida = FALSE
");

$stmt->execute([$id_usuario]);

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);


header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'quantidade' => (int) $resultado['quantidade'],
    'conversas_nao_lidas' => (int) $resultado['conversas_nao_lidas']
]);

exit;