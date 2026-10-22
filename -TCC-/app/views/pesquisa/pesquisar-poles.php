<?php

require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$pesquisa = trim((string) ($_GET['pesquisa'] ?? ''));

if (mb_strlen($pesquisa) < 2 || mb_strlen($pesquisa) > 80) {
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
