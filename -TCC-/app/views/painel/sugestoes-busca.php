<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['usuario']['id'])) {
    http_response_code(401);
    echo json_encode(['locais' => [], 'eventos' => []]);
    exit;
}

$termo = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($termo) < 2 || mb_strlen($termo) > 100) {
    echo json_encode(['locais' => [], 'eventos' => []]);
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$like = '%' . $termo . '%';

try {
    $sqlLocais = "
        SELECT id_local, nome_local, endereco_local
        FROM LocalEsp
        WHERE status_local = 'aprovado'
          AND endereco_local LIKE '%Diadema%'
          AND (nome_local LIKE ? OR endereco_local LIKE ?)
        ORDER BY nome_local LIMIT 8
    ";
    $locais = $conn->prepare($sqlLocais);
    $locais->execute([$like, $like]);

    $sqlEventos = "
        SELECT ev.id_evento, ev.nome_evento, e.nome_esporte, l.nome_local
        FROM Evento ev
        INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
        INNER JOIN LocalEsp l ON l.id_local = ev.id_local
        WHERE ev.status_evento = 'ativo'
          AND TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()
          AND (ev.nome_evento LIKE ? OR l.nome_local LIKE ? OR e.nome_esporte LIKE ?)
        ORDER BY ev.data_evento, ev.horario_evento LIMIT 8
    ";
    $eventos = $conn->prepare($sqlEventos);
    $eventos->execute([$like, $like, $like]);

    echo json_encode([
        'locais' => $locais->fetchAll(PDO::FETCH_ASSOC),
        'eventos' => $eventos->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (PDOException $e) {
    error_log('Falha nas sugestões do painel: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['locais' => [], 'eventos' => []]);
}
