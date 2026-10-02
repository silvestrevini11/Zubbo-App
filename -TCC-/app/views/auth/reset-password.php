<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

$token = trim($_GET['token'] ?? '');
$destino = '/-TCC-/public/reset-password.php';
if ($token !== '') {
    $destino .= '?token=' . urlencode($token);
}

header('Location: ' . $destino, true, 302);
exit;
