<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verificar-email.php');
    exit;
}

zubbo_require_csrf();

$cadastro = $_SESSION['cadastro_pendente'] ?? null;
if (!is_array($cadastro)) {
    header('Location: cadastro.php');
    exit;
}

$email = strtolower((string) ($cadastro['email'] ?? ''));
$codigo = trim((string) ($_POST['codigo'] ?? ''));

if (zubbo_rate_limit_exceeded('verificacao_email', 8, 600, $email)) {
    $_SESSION['erro_verificacao'] = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
    header('Location: verificar-email.php');
    exit;
}

if (!preg_match('/^[0-9]{6}$/', $codigo)) {
    zubbo_rate_limit_hit('verificacao_email', 600, $email);
    $_SESSION['erro_verificacao'] = 'Digite um código válido de 6 números.';
    header('Location: verificar-email.php');
    exit;
}

$expiracao = $cadastro['expiracao'] ?? 0;
$expiraEm = is_numeric($expiracao)
    ? (int) $expiracao
    : (int) strtotime((string) $expiracao);

$codigoEsperado = (string) ($cadastro['codigo'] ?? '');

if ($expiraEm <= 0 || $expiraEm < time()) {
    unset($_SESSION['cadastro_pendente']);
    $_SESSION['erro_verificacao'] = 'Esse código expirou. Faça o cadastro novamente.';
    header('Location: cadastro.php?erro=codigo_expirado');
    exit;
}

if ($codigoEsperado === '' || !hash_equals($codigoEsperado, $codigo)) {
    zubbo_rate_limit_hit('verificacao_email', 600, $email);
    $_SESSION['cadastro_pendente']['tentativas_codigo'] =
        (int) ($_SESSION['cadastro_pendente']['tentativas_codigo'] ?? 0) + 1;
    $_SESSION['erro_verificacao'] = 'Código de verificação incorreto.';
    header('Location: verificar-email.php');
    exit;
}

zubbo_rate_limit_reset('verificacao_email', $email);
$_SESSION['email_verificado'] = true;
header('Location: escolher-esportes.php');
exit;
