<?php

session_start();

include __DIR__.'/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = $_POST['email'];
$senha = $_POST['password'];

$stmt = $conn->prepare("
    SELECT id_user, nome_user, email_user, senha_user
    FROM Usuario
    WHERE email_user = ?
");

$stmt->execute([$email]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {

    $_SESSION['erro_login'] = 'E-mail ou senha incorretos.';

    header('Location: login.php');
    exit;
}

if (!password_verify($senha, $usuario['senha_user'])) {

    $_SESSION['erro_login'] = 'E-mail ou senha incorretos.';

    header('Location: login.php');
    exit;
}


// Cria a sessão do usuário
$_SESSION['usuario'] = [
    'id' => $usuario['id_user'],
    'nome' => $usuario['nome_user'],
    'email' => $usuario['email_user']
];

header('Location: ../painel/painel-inicial.php');
exit;