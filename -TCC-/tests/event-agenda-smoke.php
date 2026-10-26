<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/EventoAgendaService.php';

$agora = new DateTimeImmutable('2026-10-26 12:30:15', new DateTimeZone('America/Sao_Paulo'));
$casos = [
    ['2026-10-27', '05:30', 60, true],
    ['2026-10-27', '22:30', 60, true],
    ['2026-10-27', '12:00', 60, true],
    ['2026-10-27', '12:30', 90, true],
    ['2026-11-25', '22:30', 60, true],
    ['2026-11-26', '12:00', 60, false],
    ['2028-02-12', '12:00', 60, false],
    ['2026-10-25', '12:00', 60, false],
    ['2026-10-26', '12:30', 60, false],
    ['2026-10-26', '13:00', 60, true],
    ['2026-10-27', '00:00', 60, false],
    ['2026-10-27', '04:30', 60, false],
    ['2026-10-27', '05:00', 60, false],
    ['2026-10-27', '23:00', 60, false],
    ['2026-10-27', '23:30', 60, false],
    ['2026-10-27', '12:15', 60, false],
    ['2026-10-27', '12:45', 60, false],
    ['2026-10-27', '12:30:01', 60, false],
    ['2026-10-27', '5:30', 60, false],
    ['2026-02-30', '12:00', 60, false],
    ['2026-11-31', '12:00', 60, false],
    ['2026-10-27', '24:00', 60, false],
    ['2026-10-27', '22:30', 120, false],
    ['2026-10-27', '22:30', 90, true],
];
foreach ($casos as [$data, $horario, $duracao, $valido]) {
    $erro = zubbo_evento_validar_agenda($data, $horario, $duracao, $agora);
    if (($erro === '') !== $valido) {
        fwrite(STDERR, "FALHOU: $data $horario ($duracao min): $erro\n");
        exit(1);
    }
}
// Limite usa dias de calendário e o fuso do sistema, inclusive ao mudar de ano.
$virada = new DateTimeImmutable('2026-12-20 23:59:59', $agora->getTimezone());
if (zubbo_evento_validar_agenda('2027-01-19', '22:30', 60, $virada) !== ''
    || zubbo_evento_validar_agenda('2027-01-20', '05:30', 60, $virada) === '') {
    throw new RuntimeException('Limite de 30 dias incorreto na virada do ano.');
}
$horarios = zubbo_evento_horarios();
if (count($horarios) !== 35 || $horarios[0] !== '05:30' || end($horarios) !== '22:30') {
    throw new RuntimeException('Lista de horários incorreta.');
}
echo 'OK: 27 verificações de agenda, limites de data e horários.' . PHP_EOL;
