<?php
declare(strict_types=1);

/**
 * Conversas privadas somente ficam disponíveis para amizades aceitas.
 * Pedidos pendentes não criam registros na tabela Amizade.
 */
function zubbo_amizade_aceita(PDO $conn, int $usuario, int $outro): bool
{
    if ($usuario < 1 || $outro < 1 || $usuario === $outro) {
        return false;
    }

    $id1 = min($usuario, $outro);
    $id2 = max($usuario, $outro);
    $stmt = $conn->prepare("
        SELECT 1
        FROM Amizade a
        INNER JOIN Usuario u1 ON u1.id_user = a.id_user_1 AND u1.status_user = 'ativo'
        INNER JOIN Usuario u2 ON u2.id_user = a.id_user_2 AND u2.status_user = 'ativo'
        WHERE a.id_user_1 = ? AND a.id_user_2 = ?
        LIMIT 1
    ");
    $stmt->execute([$id1, $id2]);
    return (bool) $stmt->fetchColumn();
}
