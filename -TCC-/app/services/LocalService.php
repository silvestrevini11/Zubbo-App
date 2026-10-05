<?php
declare(strict_types=1);

final class LocalService
{
    public static function listarAprovados(PDO $conn): array
    {
        $stmt = $conn->query(
            "SELECT id_local, nome_local, endereco_local
             FROM LocalEsp
             WHERE status_local = 'aprovado'
             ORDER BY nome_local, endereco_local"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function aprovadoExiste(PDO $conn, int $idLocal): bool
    {
        $stmt = $conn->prepare(
            "SELECT 1 FROM LocalEsp
             WHERE id_local = ? AND status_local = 'aprovado'
             LIMIT 1"
        );
        $stmt->execute([$idLocal]);

        return (bool) $stmt->fetchColumn();
    }
}
