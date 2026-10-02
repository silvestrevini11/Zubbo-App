<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
zubbo_reject_cross_site_post();

if (!isset($_SESSION['cadastro_pendente'])) {
    header('Location: cadastro.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verificar-email.php');
    exit;
}

$cadastro = $_SESSION['cadastro_pendente'];
$codigo = trim($_POST['codigo'] ?? '');

if (strtotime((string) $cadastro['expiracao']) < time()) {
    unset($_SESSION['cadastro_pendente']);
    header('Location: cadastro.php?erro=codigo_expirado');
    exit;
}

$tentativas = (int) ($cadastro['tentativas_codigo'] ?? 0);
if ($tentativas >= 5) {
    unset($_SESSION['cadastro_pendente']);
    header('Location: cadastro.php?erro=tentativas');
    exit;
}

if (!preg_match('/^[0-9]{6}$/', $codigo) || !hash_equals((string) $cadastro['codigo'], $codigo)) {
    $_SESSION['cadastro_pendente']['tentativas_codigo'] = $tentativas + 1;
    $_SESSION['erro_verificacao'] = 'Código de verificação incorreto.';
    header('Location: verificar-email.php');
    exit;
}

$_SESSION['email_verificado'] = true;
$_SESSION['cadastro_pendente']['tentativas_codigo'] = 0;

header('Location: escolher-esportes.php');
exit;
