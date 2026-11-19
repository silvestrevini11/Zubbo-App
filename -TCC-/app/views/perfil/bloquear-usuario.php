<?php
require_once __DIR__ . '/../../middleware/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
zubbo_require_csrf();
$id = (int)$_SESSION['usuario']['id'];
$outro = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT);
$acao = (string)($_POST['acao'] ?? '');
if (!$outro || $outro === $id || !in_array($acao,['bloquear','desbloquear'],true)) {
    http_response_code(400); exit('Dados inválidos.');
}
try {
    $conn->beginTransaction();
    if ($acao === 'bloquear') {
        $stmt = $conn->prepare("SELECT 1 FROM Usuario WHERE id_user = ? AND status_user = 'ativo'");
        $stmt->execute([$outro]);
        if (!$stmt->fetchColumn()) throw new RuntimeException('Usuário indisponível.');
        $conn->prepare('INSERT IGNORE INTO Bloqueio_Usuario (id_bloqueador,id_bloqueado) VALUES (?,?)')->execute([$id,$outro]);
        $conn->prepare('DELETE FROM Amizade WHERE (id_user_1=? AND id_user_2=?) OR (id_user_1=? AND id_user_2=?)')->execute([$id,$outro,$outro,$id]);
        $conn->prepare('DELETE FROM Solicitacao_Amizade WHERE (id_remetente=? AND id_destinatario=?) OR (id_remetente=? AND id_destinatario=?)')->execute([$id,$outro,$outro,$id]);
        $conn->prepare("DELETE FROM Notificacao WHERE tipo IN ('amizade','amizade_aceita')
                        AND ((id_destinatario=? AND id_remetente=?) OR (id_destinatario=? AND id_remetente=?))")
             ->execute([$id,$outro,$outro,$id]);
    } else {
        $conn->prepare('DELETE FROM Bloqueio_Usuario WHERE id_bloqueador = ? AND id_bloqueado = ?')->execute([$id,$outro]);
    }
    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Falha ao bloquear/desbloquear contato: '.$e->getMessage());
    http_response_code(500); exit('Não foi possível atualizar o bloqueio.');
}
header('Location: perfil-ver.php?id='.$outro,true,303); exit;
