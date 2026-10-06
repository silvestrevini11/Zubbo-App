<?php

include __DIR__.'/../../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$pesquisa = trim($_GET['pesquisa'] ?? '');

if ($pesquisa === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id_local, nome_local, endereco_local, tipo_local
    FROM LocalEsp
    WHERE status_local = 'aprovado'
      AND (nome_local LIKE ? OR endereco_local LIKE ?)
    ORDER BY nome_local
    LIMIT 20
");

$stmt->execute(['%' . $pesquisa . '%', '%' . $pesquisa . '%']);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
