<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/mail.php';


$nome = trim($_POST['name-txt'] ?? '');
$email = trim($_POST['email-txt'] ?? '');
$telefone = preg_replace('/\D/', '', $_POST['telefone-tel'] ?? '');
$senha = $_POST['Senha-pass'] ?? '';
$confirmarSenha = $_POST['confirmar-senha'] ?? '';
$dataNascimento = $_POST['data-nasc'] ?? '';

if (
    $nome === ''
    || mb_strlen($nome) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($telefone) !== 11
    || strlen($senha) < 8
    || $senha !== $confirmarSenha
    || $dataNascimento === ''
) {
    header('Location: cadastro.php?erro=dados');
    exit;
}

$stmt = $conn->prepare('SELECT id_user FROM Usuario WHERE email_user = ? LIMIT 1');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    header('Location: cadastro.php?erro=email');
    exit;
}

$codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiracao = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$_SESSION['cadastro_pendente'] = [
    'nome' => $nome,
    'email' => $email,
    'telefone' => $telefone,
    'senha' => password_hash($senha, PASSWORD_DEFAULT),
    'data_nascimento' => $dataNascimento,
    'codigo' => $codigo,
    'expiracao' => $expiracao,
    'tentativas_codigo' => 0,
];

try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    zubbo_configurar_mail($mail);
    $mail->addAddress($email, $nome);
    $mail->Subject = 'Código de verificação - Zubbo';
    $mail->isHTML(true);

    $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $mail->Body = "<h2>Verifique seu e-mail</h2><p>Olá, <strong>{$nomeSeguro}</strong>.</p><p>Seu código de verificação é <strong>{$codigo}</strong>.</p><p>Ele expira em 10 minutos.</p>";
    $mail->AltBody = "Seu código de verificação do Zubbo é: {$codigo}. Ele expira em 10 minutos.";
    $mail->send();
} catch (Throwable $e) {
    error_log('Falha ao enviar verificação: ' . $e->getMessage());
    unset($_SESSION['cadastro_pendente']);
    header('Location: cadastro.php?erro=email_envio');
    exit;
}

header('Location: verificar-email.php');
exit;
