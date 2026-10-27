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
    ['2026-11-26', '22:30', 60, true],
    ['2026-11-27', '12:00', 60, false],
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
// O limite é o mesmo dia do mês seguinte, inclusive ao mudar de ano.
$virada = new DateTimeImmutable('2026-12-20 23:59:59', $agora->getTimezone());
if (zubbo_evento_validar_agenda('2027-01-20', '22:30', 60, $virada) !== ''
    || zubbo_evento_validar_agenda('2027-01-21', '05:30', 60, $virada) === '') {
    throw new RuntimeException('Limite mensal incorreto na virada do ano.');
}
$dia27 = new DateTimeImmutable('2026-10-27 12:00:00', $agora->getTimezone());
if (zubbo_evento_data_limite($dia27)->format('Y-m-d') !== '2026-11-27'
    || zubbo_evento_validar_agenda('2026-11-27', '12:00', 60, $dia27) !== '') {
    throw new RuntimeException('Limite de 27/10 deveria ser 27/11.');
}
$fimMes = new DateTimeImmutable('2027-01-31 12:00:00', $agora->getTimezone());
$fimMesBissexto = new DateTimeImmutable('2028-01-31 12:00:00', $agora->getTimezone());
if (zubbo_evento_data_limite($fimMes)->format('Y-m-d') !== '2027-02-28'
    || zubbo_evento_data_limite($fimMesBissexto)->format('Y-m-d') !== '2028-02-29') {
    throw new RuntimeException('Limite mensal incorreto no fim de fevereiro.');
}
$horarios = zubbo_evento_horarios();
if (count($horarios) !== 35 || $horarios[0] !== '05:30' || end($horarios) !== '22:30') {
    throw new RuntimeException('Lista de horários incorreta.');
}
$ocupados = [['horario_evento' => '18:00:00', 'duracao_minutos' => 90]];
$livres = zubbo_evento_horarios_disponiveis('2026-10-27', 60, $ocupados, $agora);
foreach (['17:30', '18:00', '18:30', '19:00'] as $ocupado) {
    if (in_array($ocupado, $livres, true)) throw new RuntimeException('Sobreposição exibida: ' . $ocupado);
}
if (!in_array('17:00', $livres, true) || !in_array('19:30', $livres, true)) {
    throw new RuntimeException('Horário adjacente deveria estar livre.');
}
$livresLongos = zubbo_evento_horarios_disponiveis('2026-10-27', 120, $ocupados, $agora);
if (in_array('16:30', $livresLongos, true) || !in_array('16:00', $livresLongos, true)) {
    throw new RuntimeException('Duração escolhida não aplicada.');
}
$livresAposExclusao = zubbo_evento_horarios_disponiveis('2026-10-27', 60, [], $agora);
if (!in_array('18:00', $livresAposExclusao, true)) {
    throw new RuntimeException('Evento excluído não liberou o horário.');
}
echo 'OK: agenda, conflitos por duração e liberação após exclusão.' . PHP_EOL;
