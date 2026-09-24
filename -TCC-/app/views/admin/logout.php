<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_SESSION['admin_csrf_token'], $_POST['csrf_token'])
    || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    exit('Solicitação inválida.');
}

unset($_SESSION['admin'], $_SESSION['admin_csrf_token'], $_SESSION['admin_flash']);
header('Location: login.php');
exit;
