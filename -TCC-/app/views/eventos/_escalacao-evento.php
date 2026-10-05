<?php

require_once __DIR__ . '/_regras-equipes.php';

function evento_buscar(PDO $conn, int $idEvento): ?array
{
    $stmt = $conn->prepare("
        SELECT ev.*, e.nome_esporte, l.nome_local, l.endereco_local,
               u.nome_user AS criador,
               (TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()) AS aberto
        FROM Evento ev
        INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
        INNER JOIN LocalEsp l ON l.id_local = ev.id_local
        LEFT JOIN Usuario u ON u.id_user = ev.id_criador
        WHERE ev.id_evento = ?
          AND ev.status_evento <> 'removido'
        LIMIT 1
    ");
    $stmt->execute([$idEvento]);

    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    return $evento ?: null;
}

function evento_buscar_solicitacoes(PDO $conn, int $idEvento): array
{
    $stmt = $conn->prepare("
        SELECT s.id_solicitacao, s.id_user, s.time_num, s.numero_vaga,
               s.status_solicitacao, s.data_solicitacao, s.data_resposta,
               u.nome_user
        FROM Solicitacao_Vaga_Evento s
        INNER JOIN Usuario u ON u.id_user = s.id_user
        WHERE s.id_evento = ?
          AND s.status_solicitacao IN ('pendente', 'aprovada')
        ORDER BY
            CASE WHEN s.time_num IS NULL THEN 3 ELSE s.time_num END,
            s.numero_vaga,
            s.data_solicitacao,
            s.id_solicitacao
    ");
    $stmt->execute([$idEvento]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function evento_buscar_confirmados_sem_vaga(PDO $conn, int $idEvento): array
{
    $stmt = $conn->prepare("
        SELECT u.id_user, u.nome_user
        FROM Lista_Evento le
        INNER JOIN Usuario u ON u.id_user = le.id_user
        WHERE le.id_evento = ?
          AND NOT EXISTS (
              SELECT 1
              FROM Solicitacao_Vaga_Evento s
              WHERE s.id_evento = le.id_evento
                AND s.id_user = le.id_user
                AND s.status_solicitacao = 'aprovada'
          )
        ORDER BY u.nome_user, u.id_user
    ");
    $stmt->execute([$idEvento]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function evento_montar_escalacao(array $solicitacoes, int $idUsuario): array
{
    $confirmadosTimes = [1 => [], 2 => []];
    $pendentesPorVaga = [1 => [], 2 => []];
    $confirmadosIndividuais = [];
    $pendentes = [];
    $minhaSolicitacao = null;

    foreach ($solicitacoes as $solicitacao) {
        $status = (string) $solicitacao['status_solicitacao'];
        $time = $solicitacao['time_num'] !== null ? (int) $solicitacao['time_num'] : null;
        $vaga = $solicitacao['numero_vaga'] !== null ? (int) $solicitacao['numero_vaga'] : null;

        if ((int) $solicitacao['id_user'] === $idUsuario) {
            $minhaSolicitacao = $solicitacao;
        }

        if ($status === 'aprovada') {
            if (in_array($time, [1, 2], true) && $vaga !== null && $vaga > 0) {
                $confirmadosTimes[$time][$vaga] = $solicitacao;
            } else {
                $confirmadosIndividuais[] = $solicitacao;
            }
            continue;
        }

        if ($status === 'pendente') {
            $pendentes[] = $solicitacao;

            if (in_array($time, [1, 2], true) && $vaga !== null && $vaga > 0) {
                $pendentesPorVaga[$time][$vaga][] = $solicitacao;
            }
        }
    }

    return [
        'confirmados_times' => $confirmadosTimes,
        'pendentes_por_vaga' => $pendentesPorVaga,
        'confirmados_individuais' => $confirmadosIndividuais,
        'pendentes' => $pendentes,
        'minha_solicitacao' => $minhaSolicitacao,
    ];
}
