<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/security.php';
zubbo_start_session();

if (empty($_SESSION['usuario']['id'])) {
    header('Location: /-TCC-/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
zubbo_csrf_token();
