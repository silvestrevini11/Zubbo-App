<?php
declare(strict_types=1);

function zubbo_evento_horarios(): array
{
    $horarios = [];
    for ($minutos = 330; $minutos < 1380; $minutos += 30) {
        $horarios[] = sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
    return $horarios;
}

function zubbo_evento_validar_agenda(string $data, string $horario, int $duracao, DateTimeImmutable $agora): string
{
    $dataHora = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $data . ' ' . $horario, $agora->getTimezone());
    $erros = DateTimeImmutable::getLastErrors();
    if (!$dataHora || ($erros !== false && ($erros['warning_count'] || $erros['error_count']))
        || $dataHora->format('Y-m-d H:i') !== $data . ' ' . $horario) {
        return 'Escolha uma data e um horário válidos.';
    }
    $hoje = $agora->setTime(0, 0);
    if ($dataHora->setTime(0, 0) > $hoje->modify('+30 days')) {
        return 'Escolha uma data até 30 dias a partir de hoje.';
    }
    if ($dataHora <= $agora) {
        return 'Escolha uma data e um horário futuros.';
    }
    if (!in_array($horario, zubbo_evento_horarios(), true)) {
        return 'Escolha um horário das 05:30 às 22:30, em intervalos de 30 minutos.';
    }
    if ((int)$dataHora->format('H') * 60 + (int)$dataHora->format('i') + $duracao > 1440) {
        return 'O evento deve terminar até a meia-noite. Escolha um horário anterior ou reduza a duração.';
    }
    return '';
}
