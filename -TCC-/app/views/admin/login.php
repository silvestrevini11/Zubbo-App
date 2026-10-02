<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (!empty($_SESSION['usuario']['id'])) {
    header('Location: painel.php');
    exit;
}

header('Location: ../auth/login.php');
exit;
