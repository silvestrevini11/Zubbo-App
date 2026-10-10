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
    SELECT
        ev.id_evento, ev.nome_evento, ev.data_evento, ev.horario_evento,
        e.nome_esporte, l.nome_local, l.endereco_local
    FROM Evento ev
    INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
    INNER JOIN LocalEsp l ON l.id_local = ev.id_local
    WHERE ev.status_evento = 'ativo'
      AND (
          ev.nome_evento LIKE ?
          OR e.nome_esporte LIKE ?
          OR l.nome_local LIKE ?
      )
    ORDER BY ev.data_evento, ev.horario_evento
    LIMIT 20
");

$stmt->execute([
    '%' . $pesquisa . '%',
    '%' . $pesquisa . '%',
    '%' . $pesquisa . '%'
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
