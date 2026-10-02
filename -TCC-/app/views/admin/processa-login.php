<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

if (zubbo_rate_limit_exceeded('login_admin', 5, 900)) {
    $_SESSION['admin_erro_login'] = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    zubbo_rate_limit_hit('login_admin');
    $_SESSION['admin_erro_login'] = 'E-mail ou senha incorretos.';
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT id_adm, nome_adm, email_adm, senha_adm, ativo
     FROM Administrador WHERE email_adm = ? LIMIT 1'
);
$stmt->execute([$email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$admin
    || !(bool) $admin['ativo']
    || !password_verify($senha, (string) $admin['senha_adm'])
) {
    usleep(250000);
    zubbo_rate_limit_hit('login_admin');
    $_SESSION['admin_erro_login'] = 'E-mail ou senha incorretos.';
    header('Location: login.php');
    exit;
}

zubbo_rate_limit_reset('login_admin');
session_regenerate_id(true);

$_SESSION['admin'] = [
    'id' => (int) $admin['id_adm'],
    'nome' => $admin['nome_adm'],
    'email' => $admin['email_adm'],
];
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

header('Location: painel.php');
exit;
