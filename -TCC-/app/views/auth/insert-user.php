<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form-usuario.php');
    exit;
}
include __DIR__.'/../../../config/database.php';

$nome = trim($_POST['name-txt'] ?? '');
$email = trim($_POST['email-txt'] ?? '');
$telefone = preg_replace('/\D/', '', $_POST['telefone-tel'] ?? '');
$senhaInformada = $_POST['Senha-pass'] ?? '';
$confirmacaoSenha = $_POST['confirmar-senha'] ?? '';
$data = $_POST['data-nasc'] ?? '';

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($telefone) !== 11 || $senhaInformada === '' || $senhaInformada !== $confirmacaoSenha || $data === '') {
    header('Location: cadastro.php?erro=dados');
    exit;
}

try {
    $stmt = $conn->prepare('INSERT INTO Usuario (nome_user, email_user, tel_user, senha_user, date_user) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$nome, $email, $telefone, password_hash($senhaInformada, PASSWORD_DEFAULT), $data]);
    $id_user = (int) $conn->lastInsertId();
} catch (PDOException $e) {
    // O e-mail é único no banco; não expomos detalhes internos ao usuário.
    header('Location: cadastro.php?erro=email');
    exit;
}

$_SESSION['usuario'] = [
    'id' => $id_user,
    'nome' => $nome
];

header('Location: escolher-esportes.php');
exit;
?>
