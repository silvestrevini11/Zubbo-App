<?php
require_once __DIR__ . '/../../middleware/auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode([]);
    exit;
}

$pesquisa = trim((string) ($_GET['pesquisa'] ?? ''));
if (mb_strlen($pesquisa) < 2 || mb_strlen($pesquisa) > 80) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id_user, nome_user, foto_user
    FROM Usuario
    WHERE status_user = 'ativo'
      AND nome_user LIKE ?
    ORDER BY nome_user
    LIMIT 20
");
$stmt->execute(['%' . $pesquisa . '%']);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultados = [];
foreach ($usuarios as $usuario) {
    $foto = !empty($usuario['foto_user'])
        ? zubbo_url('/' . ltrim((string) $usuario['foto_user'], '/'))
        : zubbo_url('/public/imagem/blank.png');
    $resultados[] = [
        'id_user' => (int) $usuario['id_user'],
        'nome' => $usuario['nome_user'],
        'foto' => $foto,
    ];
}
echo json_encode($resultados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
