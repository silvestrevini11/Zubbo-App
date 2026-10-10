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
        // A identidade administrativa é o ID da conta, nunca o e-mail.
        // Contas legadas sem id_user precisam de migração manual explícita.
        if (!self::possuiRelacaoUsuario($conn)) {
            error_log('Controle administrativo legado sem id_user: execute migration 002.');
            return null;
        }

        $stmt = $conn->prepare(
            'SELECT id_adm FROM Administrador WHERE id_user = ? AND ativo = 1 LIMIT 1'
        );
        $stmt->execute([$idUsuario]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public static function conceder(PDO $conn, array $usuario): int
    {
        $idUsuario = (int) $usuario['id_user'];
        $email = (string) $usuario['email_user'];
        $nome = (string) $usuario['nome_user'];

        $stmt = self::possuiRelacaoUsuario($conn)
            ? $conn->prepare('SELECT id_adm FROM Administrador WHERE id_user = ? OR email_adm = ? LIMIT 1')
            : $conn->prepare('SELECT id_adm FROM Administrador WHERE email_adm = ? LIMIT 1');

        self::possuiRelacaoUsuario($conn)
            ? $stmt->execute([$idUsuario, $email])
            : $stmt->execute([$email]);

        $idExistente = $stmt->fetchColumn();
        // O comando de provisionamento é explícito, mas jamais deve reatribuir
        // silenciosamente uma conta administrativa que pertença a outro usuário.
        if ($idExistente !== false && self::possuiRelacaoUsuario($conn)) {
            $dono = $conn->prepare('SELECT id_user FROM Administrador WHERE id_adm = ? LIMIT 1');
            $dono->execute([(int) $idExistente]);
            $idDono = $dono->fetchColumn();
            if ($idDono !== null && $idDono !== false && (int) $idDono !== $idUsuario) {
                throw new RuntimeException('Administrador já associado a outro usuário.');
            }
        }
        $senhaLegada = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        if ($idExistente !== false) {
            if (self::possuiRelacaoUsuario($conn)) {
                $conn->prepare(
                    'UPDATE Administrador SET id_user = ?, nome_adm = ?, email_adm = ?, senha_adm = ?, ativo = 1 WHERE id_adm = ?'
                )->execute([$idUsuario, $nome, $email, $senhaLegada, (int) $idExistente]);
            } else {
                $conn->prepare(
                    'UPDATE Administrador SET nome_adm = ?, email_adm = ?, senha_adm = ?, ativo = 1 WHERE id_adm = ?'
                )->execute([$nome, $email, $senhaLegada, (int) $idExistente]);
            }

            return (int) $idExistente;
        }

        if (self::possuiRelacaoUsuario($conn)) {
            $stmt = $conn->prepare(
                'INSERT INTO Administrador (id_user, nome_adm, email_adm, senha_adm, ativo) VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute([$idUsuario, $nome, $email, $senhaLegada]);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO Administrador (nome_adm, email_adm, senha_adm, ativo) VALUES (?, ?, ?, 1)'
            );
            $stmt->execute([$nome, $email, $senhaLegada]);
        }

        return (int) $conn->lastInsertId();
    }
}
