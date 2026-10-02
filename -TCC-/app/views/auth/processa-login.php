<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

if (zubbo_rate_limit_exceeded('login_usuario', 5, 900)) {
    $_SESSION['erro_login'] = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    zubbo_rate_limit_hit('login_usuario');
    $_SESSION['erro_login'] = 'E-mail ou senha incorretos.';
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT id_user, nome_user, email_user, senha_user, status_user
     FROM Usuario WHERE email_user = ? LIMIT 1'
);
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$usuario
    || $usuario['status_user'] !== 'ativo'
    || !password_verify($senha, $usuario['senha_user'])
) {
    usleep(250000);
    zubbo_rate_limit_hit('login_usuario');
    $_SESSION['erro_login'] = 'E-mail ou senha incorretos.';
    header('Location: login.php');
    exit;
}

zubbo_rate_limit_reset('login_usuario');
session_regenerate_id(true);

$_SESSION['usuario'] = [
    'id' => (int) $usuario['id_user'],
    'nome' => $usuario['nome_user'],
    'email' => $usuario['email_user'],
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: ../painel/painel-inicial.php');
exit;
