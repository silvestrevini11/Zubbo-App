<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/logger.php';
require_once __DIR__ . '/../../services/AdminService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

zubbo_require_csrf();

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$senha = (string) ($_POST['password'] ?? '');
$scope = 'login_usuario';

if (zubbo_rate_limit_exceeded($scope, 5, 900, $email)) {
    zubbo_log('warning', 'auth.login_rate_limited', ['reason_code' => 'too_many_attempts']);
    $_SESSION['erro_login'] = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
    header('Location: login.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    zubbo_rate_limit_hit($scope, 900, $email);
    zubbo_log('warning', 'auth.login_denied', ['reason_code' => 'invalid_credentials']);
    $_SESSION['erro_login'] = 'E-mail ou senha incorretos.';
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT id_user, nome_user, email_user, senha_user, status_user
     FROM Usuario
     WHERE email_user = ?
     LIMIT 1'
);
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$usuario
    || $usuario['status_user'] !== 'ativo'
    || !password_verify($senha, (string) $usuario['senha_user'])
) {
    usleep(250000);
    zubbo_rate_limit_hit($scope, 900, $email);
    $_SESSION['erro_login'] = 'E-mail ou senha incorretos.';
    header('Location: login.php');
    exit;
}

zubbo_rate_limit_reset($scope, $email);
session_regenerate_id(true);

unset($_SESSION['admin'], $_SESSION['admin_csrf_token'], $_SESSION['admin_flash']);

$_SESSION['usuario'] = [
    'id' => (int) $usuario['id_user'],
    'nome' => $usuario['nome_user'],
    'email' => $usuario['email_user'],
];

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['credential_fingerprint'] = hash('sha256', (string) $usuario['senha_user']);
zubbo_log('info', 'auth.login_success', ['user_id' => (int) $usuario['id_user']]);

$idAdmin = AdminService::buscarIdAtivo(
    $conn,
    (int) $usuario['id_user'],
    (string) $usuario['email_user']
);

if ($idAdmin !== null) {
    $_SESSION['admin'] = [
        'id' => $idAdmin,
        'id_user' => (int) $usuario['id_user'],
        'nome' => $usuario['nome_user'],
        'email' => $usuario['email_user'],
    ];
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

    header('Location: ../admin/painel.php');
    exit;
}

header('Location: ../painel/Painel-inicial.php');
exit;
