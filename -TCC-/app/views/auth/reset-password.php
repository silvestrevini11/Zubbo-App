<?php
$token = trim((string) ($_GET['token'] ?? ''));
$destino = '../../../public/reset-password.php';

if (preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $destino .= '?token=' . urlencode($token);
}

header('Location: ' . $destino, true, 302);
exit;
