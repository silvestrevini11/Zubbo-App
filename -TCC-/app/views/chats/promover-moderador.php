<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../services/GrupoService.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}
zubbo_require_csrf();
$grupoId = filter_var($_POST['id_grupo'] ?? null, FILTER_VALIDATE_INT);
$alvo = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT);
if (!$grupoId || $grupoId < 1 || !$alvo || $alvo < 1) {
    http_response_code(400);
    exit('Grupo ou integrante inválido.');
}
try {
    zubbo_grupo_promover($conn, $grupoId, (int)$_SESSION['usuario']['id'], $alvo);
    $_SESSION['grupo_moderacao_sucesso'] = 'Integrante promovido a moderador.';
} catch (DomainException $e) {
    $_SESSION['grupo_moderacao_erro'] = $e->getMessage();
} catch (Throwable $e) {
    error_log('Falha na moderação do grupo: ' . get_class($e));
    $_SESSION['grupo_moderacao_erro'] = 'Não foi possível atualizar a moderação.';
}
header('Location: configuracoes-grupo.php?id_grupo=' . $grupoId, true, 303);
exit;
