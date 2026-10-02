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
     FROM Usuario
     WHERE email_user = ?
     LIMIT 1'
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

unset(
    $_SESSION['admin'],
    $_SESSION['admin_csrf_token'],
    $_SESSION['admin_flash']
);

$_SESSION['usuario'] = [
    'id' => (int) $usuario['id_user'],
    'nome' => $usuario['nome_user'],
    'email' => $usuario['email_user'],
];

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$stmtAdmin = $conn->prepare(
    'SELECT id_adm
     FROM Administrador
     WHERE email_adm = ?
       AND ativo = 1
     LIMIT 1'
);
$stmtAdmin->execute([$usuario['email_user']]);
$idAdmin = $stmtAdmin->fetchColumn();

if ($idAdmin !== false) {
    $_SESSION['admin'] = [
        'id' => (int) $idAdmin,
        'id_user' => (int) $usuario['id_user'],
        'nome' => $usuario['nome_user'],
        'email' => $usuario['email_user'],
    ];
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

    header('Location: ../admin/painel.php');
    exit;
}

header('Location: ../painel/painel-inicial.php');
exit;
