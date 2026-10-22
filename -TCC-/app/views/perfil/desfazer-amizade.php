<?php
require_once __DIR__ . '/../../middleware/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
zubbo_require_csrf();
$idUsuario = (int) $_SESSION['usuario']['id'];
$idAmigo = filter_var($_POST['id_amigo'] ?? null, FILTER_VALIDATE_INT);
if (!$idAmigo || $idAmigo === $idUsuario) {
    http_response_code(400);
    exit('Usuário inválido.');
}
$id1 = min($idUsuario, $idAmigo);
$id2 = max($idUsuario, $idAmigo);
try {
    $conn->beginTransaction();
    $stmt = $conn->prepare("DELETE FROM Amizade WHERE (id_user_1 = ? AND id_user_2 = ?) OR (id_user_1 = ? AND id_user_2 = ?)");
    $stmt->execute([$id1, $id2, $id2, $id1]);
    $conn->prepare("DELETE FROM Solicitacao_Amizade
        WHERE (id_remetente = ? AND id_destinatario = ?) OR (id_remetente = ? AND id_destinatario = ?)")
        ->execute([$idUsuario, $idAmigo, $idAmigo, $idUsuario]);
    $conn->commit();
    header('Location: perfil-ver.php?id='.$idAmigo.'&amizade=removida', true, 303);
    exit;
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Falha ao desfazer amizade: '.$e->getMessage());
    http_response_code(500);
    exit('Não foi possível desfazer a amizade.');
}
