<?php

include __DIR__.'/../../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$pesquisa = trim($_GET['pesquisa'] ?? '');

if ($pesquisa === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        id_user,
        nome_user,
        foto_user
    FROM Usuario
    WHERE nome_user LIKE ?
    ORDER BY nome_user
    LIMIT 20
");

$stmt->execute([
    '%' . $pesquisa . '%'
]);

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultados = [];

foreach ($usuarios as $usuario) {

    $foto = !empty($usuario['foto_user'])
        ? '/-TCC-/' . $usuario['foto_user']
        : '/-TCC-/public/imagem/blank.png';

    $resultados[] = [
        'id_user' => $usuario['id_user'],
        'nome' => $usuario['nome_user'],
        'foto' => $foto
    ];
}

echo json_encode($resultados);