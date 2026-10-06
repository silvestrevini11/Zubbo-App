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
