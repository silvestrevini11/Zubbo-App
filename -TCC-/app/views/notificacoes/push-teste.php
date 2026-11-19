<?php
declare(strict_types=1);
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../services/PushService.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405); echo '{"erro":"metodo"}'; exit;
}
zubbo_require_csrf();
if (!PushService::configured()) {
    http_response_code(503); echo '{"erro":"servidor_nao_configurado"}'; exit;
}
if ((int)($_SESSION['push_teste_em'] ?? 0) > time() - 30) {
    http_response_code(429); echo '{"erro":"aguarde_30_segundos"}'; exit;
}
$_SESSION['push_teste_em'] = time();
PushService::sendToUser($conn, (int)$_SESSION['usuario']['id'], 'Zubbo', 'Teste de notificação ativado!', '/app/views/notificacoes/notificacoes.php');
echo '{"ok":true}';
