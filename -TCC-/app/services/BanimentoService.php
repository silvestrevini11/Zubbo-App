<?php
declare(strict_types=1);
require_once __DIR__ . '/GrupoService.php';

/** Chamado pelo painel autenticado, dentro da transação da decisão administrativa. */
function zubbo_banir_usuario(PDO $db, int $usuario): void
{
    if (!$db->inTransaction()) throw new LogicException('Banimento exige uma transação.');
    $lock = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
    $st = $db->prepare('SELECT id_user FROM Usuario WHERE id_user=?' . $lock);
    $st->execute([$usuario]);
    if ($st->fetchColumn() === false) throw new DomainException('Usuário não encontrado.');
    $st = $db->prepare('SELECT 1 FROM Administrador WHERE id_user=? AND ativo=1');
    $st->execute([$usuario]);
    if ($st->fetchColumn() !== false) throw new DomainException('Uma conta administrativa ativa não pode ser banida.');

    // Inclui referências antigas de criador/moderação, mesmo sem participação atual.
    $st = $db->prepare('SELECT g.id_grupo FROM Grupo g WHERE g.id_criador=?
        OR EXISTS (SELECT 1 FROM Grupo_Moderador gm WHERE gm.id_grupo=g.id_grupo AND gm.id_user=?)
        OR EXISTS (SELECT 1 FROM Participantes_Conversa pc WHERE pc.id_conversa=g.id_conversa AND pc.id_user=?)
        ORDER BY g.id_grupo');
    $st->execute([$usuario, $usuario, $usuario]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $grupoId) {
        $grupo = zubbo_grupo_bloquear($db, 'id_grupo', (int)$grupoId);
        zubbo_grupo_remover_integrante($db, $grupo, $usuario);
    }

    // Remove os registros associados antes de apagar seus alvos e perder os vínculos.
    $db->prepare('DELETE FROM Acao_Administrativa WHERE id_user=?
        OR id_adm IN (SELECT id_adm FROM Administrador WHERE id_user=?)
        OR id_denuncia IN (SELECT id_denuncia FROM Denuncia WHERE id_denunciante=? OR id_denunciado=?)')
        ->execute([$usuario, $usuario, $usuario, $usuario]);
    $db->prepare('DELETE FROM Denuncia WHERE id_denunciante=? OR id_denunciado=?')->execute([$usuario, $usuario]);
    $db->prepare('DELETE FROM Notificacao WHERE id_destinatario=? OR id_remetente=?
        OR id_mensagem IN (SELECT id_mensagem FROM Mensagem WHERE id_remetente=?)')
        ->execute([$usuario, $usuario, $usuario]);
    $db->prepare('DELETE FROM Mensagem WHERE id_remetente=?')->execute([$usuario]);
    $db->prepare('DELETE FROM Voto_Sugestao WHERE id_user=?
        OR id_sugestao IN (SELECT id_sugestao FROM Sugestao_Esporte WHERE id_user=?)')->execute([$usuario, $usuario]);
    $db->prepare('DELETE FROM Sugestao_Esporte WHERE id_user=?')->execute([$usuario]);

    foreach (['Grupo_Moderador', 'Participantes_Conversa', 'ParticipantesEquipe', 'Lista_Evento',
        'Solicitacao_Vaga_Evento', 'Fila_Espera_Evento', 'Usuario_Esporte', 'PushSubscription',
        'Verificacao_Email', 'Recuperacao_Senha'] as $tabela) {
        $db->prepare("DELETE FROM $tabela WHERE id_user=?")->execute([$usuario]);
    }
    $db->prepare('DELETE FROM Amizade WHERE id_user_1=? OR id_user_2=?')->execute([$usuario, $usuario]);
    $db->prepare('DELETE FROM Solicitacao_Amizade WHERE id_remetente=? OR id_destinatario=?')->execute([$usuario, $usuario]);
    $db->prepare('DELETE FROM Bloqueio_Usuario WHERE id_bloqueador=? OR id_bloqueado=?')->execute([$usuario, $usuario]);

    // Mantém os recursos compartilhados e os registros dos demais integrantes.
    $db->prepare("UPDATE Evento SET status_evento=CASE WHEN status_evento='ativo' THEN 'cancelado' ELSE status_evento END,
        id_criador=NULL WHERE id_criador=?")->execute([$usuario]);
    $db->prepare('UPDATE Equipe SET id_criador=NULL WHERE id_criador=?')->execute([$usuario]);
    $db->prepare('UPDATE LocalEsp SET id_criador=NULL WHERE id_criador=?')->execute([$usuario]);
    // IDs administrativos antigos podem sustentar confirmações de locais compartilhados.
    // Retira a identidade, sem apagar essas confirmações dos demais usuários.
    $db->prepare("UPDATE Administrador SET id_user=NULL,nome_adm='Administrador desativado',
        email_adm=?,ativo=0 WHERE id_user=?")->execute(['banido-' . $usuario . '@deleted.invalid', $usuario]);
    $db->prepare("UPDATE Usuario SET status_user='banido' WHERE id_user=?")->execute([$usuario]);
}
