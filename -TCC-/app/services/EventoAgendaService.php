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

function zubbo_evento_data_limite(DateTimeImmutable $agora): DateTimeImmutable
{
    $primeiroDiaMesSeguinte = $agora->setTime(0, 0)->modify('first day of next month');
    $dia = min((int)$agora->format('j'), (int)$primeiroDiaMesSeguinte->format('t'));
    return $primeiroDiaMesSeguinte->setDate(
        (int)$primeiroDiaMesSeguinte->format('Y'),
        (int)$primeiroDiaMesSeguinte->format('n'),
        $dia
    );
}

function zubbo_evento_horarios_disponiveis(string $data, int $duracao, array $eventos, DateTimeImmutable $agora): array
{
    $disponiveis = [];
    foreach (zubbo_evento_horarios() as $horario) {
        if (zubbo_evento_validar_agenda($data, $horario, $duracao, $agora) !== '') {
            continue;
        }
        $inicio = (int)substr($horario, 0, 2) * 60 + (int)substr($horario, 3, 2);
        $fim = $inicio + $duracao;
        foreach ($eventos as $evento) {
            $horarioOcupado = substr((string)$evento['horario_evento'], 0, 5);
            $inicioOcupado = (int)substr($horarioOcupado, 0, 2) * 60 + (int)substr($horarioOcupado, 3, 2);
            $fimOcupado = $inicioOcupado + (int)$evento['duracao_minutos'];
            $fimEvento = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $data . ' ' . $horarioOcupado, $agora->getTimezone());
            if ($fimEvento && $fimEvento->modify('+' . (int)$evento['duracao_minutos'] . ' minutes') > $agora
                && $inicio < $fimOcupado && $fim > $inicioOcupado) {
                continue 2;
            }
        }
        $disponiveis[] = $horario;
    }
    return $disponiveis;
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
    if ($dataHora->setTime(0, 0) > zubbo_evento_data_limite($agora)) {
        return 'Escolha uma data até o mesmo dia do próximo mês.';
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
