<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/security.php';
zubbo_start_session();

if (empty($_SESSION['usuario']['id'])) {
    header('Location: ' . zubbo_url('/app/views/auth/login.php'));
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Suspensão e banimento revogam o acesso, inclusive em sessões antigas.
$stmtStatus = $conn->prepare("SELECT status_user FROM Usuario WHERE id_user = ? LIMIT 1");
$stmtStatus->execute([(int) $_SESSION['usuario']['id']]);
if ($stmtStatus->fetchColumn() !== 'ativo') {
    unset($_SESSION['usuario'], $_SESSION['admin'], $_SESSION['admin_csrf_token']);
    header('Location: ' . zubbo_url('/app/views/auth/login.php'));
    exit;
}
zubbo_csrf_token();
