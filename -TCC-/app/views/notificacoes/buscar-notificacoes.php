<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];


$stmt = $conn->prepare("
    SELECT COUNT(*) AS quantidade
    FROM Notificacao
    WHERE id_destinatario = ?
      AND lida = FALSE
");

$stmt->execute([$id_usuario]);

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);


header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'quantidade' => (int) $resultado['quantidade']
]);