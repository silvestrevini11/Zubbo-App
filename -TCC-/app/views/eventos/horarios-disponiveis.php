<?php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../services/EventoAgendaService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$idLocal = filter_var($_GET['id_local'] ?? null, FILTER_VALIDATE_INT);
$duracao = filter_var($_GET['duracao_minutos'] ?? null, FILTER_VALIDATE_INT);
$data = (string)($_GET['data_evento'] ?? '');
$agora = new DateTimeImmutable('now');
$dia = DateTimeImmutable::createFromFormat('!Y-m-d', $data, $agora->getTimezone());
$hoje = $agora->setTime(0, 0);

if (!$idLocal || !in_array($duracao, [60, 90, 120], true)
    || !$dia || $dia->format('Y-m-d') !== $data
    || $dia < $hoje || $dia > zubbo_evento_data_limite($agora)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Data, local ou duração inválidos.']);
    exit;
}

try {
    $local = $conn->prepare("SELECT 1 FROM LocalEsp WHERE id_local = ? AND status_local = 'aprovado'");
    $local->execute([$idLocal]);
    if (!$local->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['erro' => 'Local indisponível.']);
        exit;
    }
    $consulta = $conn->prepare("SELECT horario_evento, duracao_minutos FROM Evento
        WHERE id_local = ? AND data_evento = ? AND status_evento = 'ativo'");
    $consulta->execute([$idLocal, $data]);
    echo json_encode(['horarios' => zubbo_evento_horarios_disponiveis(
        $data, $duracao, $consulta->fetchAll(PDO::FETCH_ASSOC), $agora
    )]);
} catch (PDOException $e) {
    error_log('Erro ao consultar horários disponíveis: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['erro' => 'Não foi possível consultar os horários.']);
}
