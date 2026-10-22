<?php
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
zubbo_require_csrf();
$idUsuario = (int) $_SESSION['usuario']['id'];
$idConversa = filter_var($_POST['id_conversa'] ?? null, FILTER_VALIDATE_INT);
if (!$idConversa || $idConversa < 1) {
    http_response_code(400);
    exit('Conversa inválida.');
}
try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("
        SELECT g.id_criador
        FROM Grupo g
        INNER JOIN Conversa c ON c.id_conversa = g.id_conversa AND c.tipo_conversa = 'grupo'
        INNER JOIN Participantes_Conversa pc ON pc.id_conversa = g.id_conversa
        WHERE g.id_conversa = ? AND pc.id_user = ?
        LIMIT 1 FOR UPDATE
    ");
    $stmt->execute([$idConversa, $idUsuario]);
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$grupo) {
        throw new RuntimeException('Você não participa deste grupo.');
    }
    if ((int) $grupo['id_criador'] === $idUsuario) {
        throw new RuntimeException('O criador não pode sair do próprio grupo.');
    }
    $conn->prepare("DELETE FROM Participantes_Conversa WHERE id_conversa = ? AND id_user = ?")
         ->execute([$idConversa, $idUsuario]);
    $conn->prepare("DELETE FROM Notificacao WHERE id_conversa = ? AND id_destinatario = ?")
         ->execute([$idConversa, $idUsuario]);
    $conn->commit();
    header('Location: chats-grupos.php?saiu=1', true, 303);
    exit;
} catch (RuntimeException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $_SESSION['grupo_saida_erro'] = $e->getMessage();
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Falha ao sair de grupo: ' . $e->getMessage());
    $_SESSION['grupo_saida_erro'] = 'Não foi possível sair do grupo.';
}
header('Location: chats-grupos.php', true, 303);
exit;
