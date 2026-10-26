<?php
declare(strict_types=1);

function zubbo_grupo_bloquear(PDO $db, string $campo, int $id): array
{
    if (!in_array($campo, ['id_grupo', 'id_conversa'], true)) throw new InvalidArgumentException('Campo inválido.');
    $lock = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
    $st = $db->prepare("SELECT * FROM Grupo WHERE $campo = ?" . $lock);
    $st->execute([$id]);
    $grupo = $st->fetch(PDO::FETCH_ASSOC);
    if (!$grupo) throw new DomainException('Grupo não encontrado.');
    return $grupo;
}

function zubbo_grupo_moderador(PDO $db, array $grupo, int $usuario): bool
{
    $st = $db->prepare("SELECT pc.id_user FROM Participantes_Conversa pc
        JOIN Usuario u ON u.id_user=pc.id_user AND u.status_user='ativo'
        WHERE pc.id_conversa=? AND pc.id_user=?
        AND (pc.id_user=? OR EXISTS (SELECT 1 FROM Grupo_Moderador gm WHERE gm.id_grupo=? AND gm.id_user=pc.id_user))");
    $st->execute([$grupo['id_conversa'], $usuario, $grupo['id_criador'], $grupo['id_grupo']]);
    return $st->fetchColumn() !== false;
}

function zubbo_grupo_promover(PDO $db, int $grupoId, int $autor, int $alvo): void
{
    $db->beginTransaction();
    try {
        $grupo = zubbo_grupo_bloquear($db, 'id_grupo', $grupoId);
        if (!zubbo_grupo_moderador($db, $grupo, $autor)) throw new DomainException('Somente moderadores podem promover integrantes.');
        $st = $db->prepare("SELECT pc.id_user FROM Participantes_Conversa pc JOIN Usuario u ON u.id_user=pc.id_user
            WHERE pc.id_conversa=? AND pc.id_user=? AND u.status_user='ativo'");
        $st->execute([$grupo['id_conversa'], $alvo]);
        if ($st->fetchColumn() === false) throw new DomainException('Selecione um integrante ativo deste grupo.');
        if (!zubbo_grupo_moderador($db, $grupo, $alvo)) {
            $db->prepare('INSERT INTO Grupo_Moderador (id_grupo,id_user) VALUES (?,?)')->execute([$grupoId, $alvo]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}

function zubbo_grupo_sair(PDO $db, int $conversa, int $usuario): void
{
    $db->beginTransaction();
    try {
        $grupo = zubbo_grupo_bloquear($db, 'id_conversa', $conversa);
        $st = $db->prepare('SELECT id_user FROM Participantes_Conversa WHERE id_conversa=? AND id_user=?');
        $st->execute([$conversa, $usuario]);
        if ($st->fetchColumn() === false) throw new DomainException('Você não participa deste grupo.');
        $moderador = zubbo_grupo_moderador($db, $grupo, $usuario);
        $db->prepare('DELETE FROM Grupo_Moderador WHERE id_grupo=? AND id_user=?')->execute([$grupo['id_grupo'], $usuario]);
        $db->prepare('DELETE FROM Participantes_Conversa WHERE id_conversa=? AND id_user=?')->execute([$conversa, $usuario]);
        $db->prepare('DELETE FROM Notificacao WHERE id_conversa=? AND id_destinatario=?')->execute([$conversa, $usuario]);
        $substituto = null;
        if ($moderador) {
            $st = $db->prepare("SELECT pc.id_user FROM Participantes_Conversa pc
                JOIN Usuario u ON u.id_user=pc.id_user AND u.status_user='ativo'
                WHERE pc.id_conversa=? AND pc.id_user<>COALESCE(?,0)
                AND NOT EXISTS (SELECT 1 FROM Grupo_Moderador gm WHERE gm.id_grupo=? AND gm.id_user=pc.id_user)");
            $st->execute([$conversa, $grupo['id_criador'], $grupo['id_grupo']]);
            $candidatos = $st->fetchAll(PDO::FETCH_COLUMN);
            if ($candidatos) {
                $substituto = (int)$candidatos[random_int(0, count($candidatos)-1)];
                $db->prepare('INSERT INTO Grupo_Moderador (id_grupo,id_user) VALUES (?,?)')->execute([$grupo['id_grupo'], $substituto]);
            }
        }
        if ((int)$grupo['id_criador'] === $usuario) {
            if ($substituto === null) {
                $st = $db->prepare("SELECT gm.id_user FROM Grupo_Moderador gm
                    JOIN Participantes_Conversa pc ON pc.id_user=gm.id_user AND pc.id_conversa=?
                    JOIN Usuario u ON u.id_user=gm.id_user AND u.status_user='ativo'
                    WHERE gm.id_grupo=? ORDER BY gm.id_user");
                $st->execute([$conversa, $grupo['id_grupo']]);
                $restantes = $st->fetchAll(PDO::FETCH_COLUMN);
                if ($restantes) $substituto = (int)$restantes[random_int(0, count($restantes)-1)];
            }
            $db->prepare('UPDATE Grupo SET id_criador=? WHERE id_grupo=?')->execute([$substituto, $grupo['id_grupo']]);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
