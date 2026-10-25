<?php
declare(strict_types=1);

/**
 * Salva uma notificação para um destinatário ativo dentro da transação atual.
 * Web Push é um canal adicional; a notificação persistida continua disponível.
 */
function zubbo_notificar(PDO $conn, int $destinatario, int $remetente, string $tipo,
    ?int $idEvento = null, ?int $idConversa = null): bool
{
    $tipos = ['vaga_solicitada', 'vaga_aprovada', 'grupo_adicionado'];
    if (!in_array($tipo, $tipos, true) || $destinatario <= 0 ||
        $remetente <= 0 || $destinatario === $remetente) {
        return false;
    }
    if (($tipo === 'grupo_adicionado' && !$idConversa) ||
        ($tipo !== 'grupo_adicionado' && !$idEvento)) {
        return false;
    }
    $sql = "INSERT INTO Notificacao
      (id_destinatario,id_remetente,id_conversa,id_evento,tipo)
      SELECT u.id_user,?,?,?,? FROM Usuario u
      WHERE u.id_user=? AND u.status_user='ativo'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$remetente, $idConversa, $idEvento, $tipo, $destinatario]);
    return $stmt->rowCount() === 1;
}
