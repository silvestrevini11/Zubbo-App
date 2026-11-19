<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../services/GrupoService.php';

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
    zubbo_grupo_sair($conn, $idConversa, $idUsuario);
    header('Location: chats-grupos.php?saiu=1', true, 303);
    exit;
} catch (DomainException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $_SESSION['grupo_saida_erro'] = $e->getMessage();
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Falha ao sair de grupo: ' . $e->getMessage());
    $_SESSION['grupo_saida_erro'] = 'Não foi possível sair do grupo.';
}
header('Location: chats-grupos.php', true, 303);
exit;
