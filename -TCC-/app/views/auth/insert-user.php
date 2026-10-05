<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit;
}

zubbo_require_csrf();

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/mail.php';

$nome = trim((string) ($_POST['name-txt'] ?? ''));
$email = strtolower(trim((string) ($_POST['email-txt'] ?? '')));
$telefone = preg_replace('/\D/', '', (string) ($_POST['telefone-tel'] ?? ''));
$senha = (string) ($_POST['Senha-pass'] ?? '');
$confirmarSenha = (string) ($_POST['confirmar-senha'] ?? '');
$dataNascimento = (string) ($_POST['data-nasc'] ?? '');

if (zubbo_rate_limit_exceeded('cadastro', 5, 3600, $email)) {
    header('Location: cadastro.php?erro=limite');
    exit;
}

if (
    $nome === ''
    || mb_strlen($nome) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($telefone) !== 11
    || strlen($senha) < 8
    || strlen($senha) > 255
    || $senha !== $confirmarSenha
    || $dataNascimento === ''
) {
    zubbo_rate_limit_hit('cadastro', 3600, $email);
    header('Location: cadastro.php?erro=dados');
    exit;
}

$stmt = $conn->prepare('SELECT id_user FROM Usuario WHERE email_user = ? LIMIT 1');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    zubbo_rate_limit_hit('cadastro', 3600, $email);
    header('Location: cadastro.php?erro=email');
    exit;
}

$codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$_SESSION['cadastro_pendente'] = [
    'nome' => $nome,
    'email' => $email,
    'telefone' => $telefone,
    'senha' => password_hash($senha, PASSWORD_DEFAULT),
    'data_nascimento' => $dataNascimento,
    'codigo' => $codigo,
    'expiracao' => time() + 600,
    'tentativas_codigo' => 0,
];

try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    zubbo_configurar_mail($mail);
    $mail->addAddress($email, $nome);
    $mail->Subject = 'Código de verificação - Zubbo';
    $mail->isHTML(true);

    $nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');

    $mail->Body =
        '<h2>Verifique seu e-mail</h2>' .
        '<p>Olá, <strong>' . $nomeSeguro . '</strong>.</p>' .
        '<p>Seu código de verificação é <strong>' . $codigoSeguro . '</strong>.</p>' .
        '<p>Ele expira em 10 minutos.</p>';
    $mail->AltBody = 'Seu código de verificação do Zubbo é: ' . $codigo . '. Ele expira em 10 minutos.';
    $mail->send();

    zubbo_rate_limit_reset('cadastro', $email);
} catch (Throwable $e) {
    error_log('Falha ao enviar verificação: ' . $e->getMessage());
    unset($_SESSION['cadastro_pendente']);
    zubbo_rate_limit_hit('cadastro', 3600, $email);
    header('Location: cadastro.php?erro=email_envio');
    exit;
}

header('Location: verificar-email.php');
exit;
