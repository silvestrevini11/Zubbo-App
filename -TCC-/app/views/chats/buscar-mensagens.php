<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];
$id_conversa = (int) ($_GET['id_conversa'] ?? 0);

if ($id_conversa <= 0) {
    http_response_code(400);
    exit;
}


/*
    Verifica se o usuário pertence à conversa
*/

$stmt = $conn->prepare("
    SELECT id_conversa
    FROM Conversa
    WHERE id_conversa = ?
      AND (id_user_a = ? OR id_user_b = ?)
");

$stmt->execute([
    $id_conversa,
    $id_usuario,
    $id_usuario
]);

if (!$stmt->fetch()) {
    http_response_code(403);
    exit;
}


/*
    Busca as mensagens
*/

$stmt = $conn->prepare("
    SELECT
        id_mensagem,
        id_remetente,
        mensagem,
        data_envio
    FROM Mensagem
    WHERE id_conversa = ?
    ORDER BY data_envio ASC, id_mensagem ASC
");

$stmt->execute([$id_conversa]);

$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
    Informa ao JavaScript quem está logado
*/

foreach ($mensagens as &$msg) {

    $msg['minha'] =
        (int) $msg['id_remetente'] === $id_usuario;

}

unset($msg);


/*
    Retorna JSON
*/

header('Content-Type: application/json; charset=utf-8');

echo json_encode($mensagens);