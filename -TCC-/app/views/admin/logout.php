<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_SESSION['admin_csrf_token'], $_POST['csrf_token'])
    || !is_string($_POST['csrf_token'])
    || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Solicitação inválida.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ../auth/login.php');
exit;
