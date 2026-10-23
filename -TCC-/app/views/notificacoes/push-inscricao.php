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
$id = (int)$_SESSION['usuario']['id'];
$acao = (string)($_POST['acao'] ?? '');
if (!in_array($acao, ['ativar', 'desativar'], true)) {
    http_response_code(400); echo '{"erro":"acao"}'; exit;
}
$entrada = (string)($_POST['subscription'] ?? '');
if (strlen($entrada) > 8192 || $entrada === '') {
    http_response_code(400); echo '{"erro":"inscricao"}'; exit;
}
try {
    $data = json_decode($entrada, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !PushService::validSubscription($data)) {
        throw new InvalidArgumentException('inscricao');
    }
    $hash = hash('sha256', $data['endpoint']);
    if ($acao === 'ativar') {
        if (!PushService::configured()) {
            http_response_code(503); echo '{"erro":"servidor_nao_configurado"}'; exit;
        }
        $db = $conn->prepare("INSERT INTO PushSubscription(id_user, endpoint_hash, dados_json)
            VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE id_user=VALUES(id_user),
                dados_json=VALUES(dados_json), atualizado_em=CURRENT_TIMESTAMP");
        $db->execute([$id,$hash,json_encode($data,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
    } else {
        $conn->prepare("DELETE FROM PushSubscription WHERE id_user=? AND endpoint_hash=?")
            ->execute([$id,$hash]);
    }
    echo json_encode(['ok'=>true],JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException|\JsonException $e) {
    http_response_code(400); echo '{"erro":"inscricao_invalida"}';
} catch (Throwable $e) {
    error_log('Zubbo push-inscricao: '.get_class($e));
    http_response_code(500); echo '{"erro":"falha_servidor"}';
}
