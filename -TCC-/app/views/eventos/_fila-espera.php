<?php
/**
 * Transfere o primeiro usuário elegível da fila para uma solicitação pendente
 * na vaga recém-liberada. Sempre executar dentro de transação com Evento bloqueado.
 */
function evento_promover_fila(PDO $conn, int $idEvento, int $time, int $vaga): void
{
    $ativo=$conn->prepare("SELECT 1 FROM Evento WHERE id_evento=? AND status_evento='ativo'
        AND TIMESTAMP(data_evento,horario_evento)>=NOW()");
    $ativo->execute([$idEvento]);
    if (!$ativo->fetchColumn()) return;

    for ($tentativas=0; $tentativas<100; $tentativas++) {
        $st = $conn->prepare("SELECT f.id_user
            FROM Fila_Espera_Evento f
            INNER JOIN Usuario u ON u.id_user = f.id_user
            WHERE f.id_evento = ?
            ORDER BY f.data_entrada, f.id_user
            LIMIT 1 FOR UPDATE");
        $st->execute([$idEvento]);
        $id = $st->fetchColumn();
        if (!$id) return;

        $conn->prepare('DELETE FROM Fila_Espera_Evento WHERE id_evento=? AND id_user=?')->execute([$idEvento,(int)$id]);
        $stAtivo = $conn->prepare("SELECT status_user FROM Usuario WHERE id_user = ?");
        $stAtivo->execute([(int)$id]);
        if ($stAtivo->fetchColumn() !== 'ativo') continue;
        $stParticipa = $conn->prepare("SELECT 1 FROM Lista_Evento WHERE id_evento=? AND id_user=?");
        $stParticipa->execute([$idEvento,(int)$id]);
        if ($stParticipa->fetchColumn()) continue;

        $st = $conn->prepare("SELECT status_solicitacao FROM Solicitacao_Vaga_Evento
          WHERE id_evento=? AND id_user=? FOR UPDATE");
        $st->execute([$idEvento,(int)$id]);
        $status = $st->fetchColumn();
        if (in_array($status,['pendente','aprovada'],true)) continue;
        $conn->prepare("INSERT INTO Solicitacao_Vaga_Evento
          (id_evento,id_user,time_num,numero_vaga,status_solicitacao,data_resposta)
          VALUES (?,?,?,?,'pendente',NULL)
          ON DUPLICATE KEY UPDATE time_num=VALUES(time_num),
          numero_vaga=VALUES(numero_vaga),status_solicitacao='pendente',
          data_solicitacao=CURRENT_TIMESTAMP,data_resposta=NULL")
          ->execute([$idEvento,(int)$id,$time,$vaga]);
        // A aprovação continua exclusiva do organizador.
        return;
    }
}
