<?php
declare(strict_types=1);

final class AdminService
{
    private static ?bool $possuiIdUsuario = null;

    public static function possuiRelacaoUsuario(PDO $conn): bool
    {
        if (self::$possuiIdUsuario !== null) {
            return self::$possuiIdUsuario;
        }

        $stmt = $conn->query("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'Administrador'
              AND COLUMN_NAME = 'id_user'
        ");

        self::$possuiIdUsuario = (bool) $stmt->fetchColumn();
        return self::$possuiIdUsuario;
    }

    public static function buscarIdAtivo(PDO $conn, int $idUsuario, string $email): ?int
    {
        if (self::possuiRelacaoUsuario($conn)) {
            $stmt = $conn->prepare("
                SELECT id_adm, id_user
                FROM Administrador
                WHERE ativo = 1
                  AND (id_user = ? OR (id_user IS NULL AND email_adm = ?))
                ORDER BY (id_user = ?) DESC
                LIMIT 1
            ");
            $stmt->execute([$idUsuario, $email, $idUsuario]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                return null;
            }

            if ($admin['id_user'] === null) {
                try {
                    $conn->prepare('UPDATE Administrador SET id_user = ? WHERE id_adm = ? AND id_user IS NULL')
                        ->execute([$idUsuario, $admin['id_adm']]);
                } catch (PDOException $e) {
                    error_log('Não foi possível vincular administrador ao usuário: ' . $e->getMessage());
                }
            }

            return (int) $admin['id_adm'];
        }

        $stmt = $conn->prepare(
            'SELECT id_adm FROM Administrador WHERE email_adm = ? AND ativo = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public static function conceder(PDO $conn, array $usuario): int
    {
        $idUsuario = (int) $usuario['id_user'];
        $email = (string) $usuario['email_user'];
        $nome = (string) $usuario['nome_user'];

        $idExistente = self::buscarIdAtivo($conn, $idUsuario, $email);
        if ($idExistente !== null) {
            if (self::possuiRelacaoUsuario($conn)) {
                $conn->prepare(
                    'UPDATE Administrador SET id_user = ?, nome_adm = ?, email_adm = ?, ativo = 1 WHERE id_adm = ?'
                )->execute([$idUsuario, $nome, $email, $idExistente]);
            } else {
                $conn->prepare(
                    'UPDATE Administrador SET nome_adm = ?, email_adm = ?, ativo = 1 WHERE id_adm = ?'
                )->execute([$nome, $email, $idExistente]);
            }
            return $idExistente;
        }

        $senhaLegada = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        if (self::possuiRelacaoUsuario($conn)) {
            $stmt = $conn->prepare(
                'INSERT INTO Administrador (id_user, nome_adm, email_adm, senha_adm, ativo)
                 VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute([$idUsuario, $nome, $email, $senhaLegada]);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO Administrador (nome_adm, email_adm, senha_adm, ativo)
                 VALUES (?, ?, ?, 1)'
            );
            $stmt->execute([$nome, $email, $senhaLegada]);
        }

        return (int) $conn->lastInsertId();
    }
}
