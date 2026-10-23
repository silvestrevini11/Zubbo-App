<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
zubbo_require_csrf();

$cadastro = $_SESSION['cadastro_pendente'] ?? null;
if (!is_array($cadastro)) {
    header('Location: cadastro.php', true, 303);
    exit;
}
$email = (string)($cadastro['email'] ?? '');
$agora = time();
$ultimo = (int)($cadastro['ultimo_envio'] ?? 0);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: cadastro.php?erro=dados', true, 303);
    exit;
}
if ($agora - $ultimo < 60) {
    $_SESSION['erro_verificacao'] = 'Aguarde 60 segundos entre os envios.';
    header('Location: verificar-email.php', true, 303);
    exit;
}
if (zubbo_rate_limit_exceeded('reenvio_codigo', 5, 3600, $email)) {
    $_SESSION['erro_verificacao'] = 'Limite de reenvios atingido. Aguarde uma hora.';
    header('Location: verificar-email.php', true, 303);
    exit;
}
$novoCodigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$demo = filter_var((string)getenv('ZUBBO_DEMO_MODE'), FILTER_VALIDATE_BOOLEAN);
if (!$demo) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
    require_once __DIR__ . '/../../../config/mail.php';
    try {
        $codigoSeguro = htmlspecialchars($novoCodigo, ENT_QUOTES, 'UTF-8');
        zubbo_enviar_email($email, 'Novo código de verificação - Zubbo',
            '<h2>Seu novo código Zubbo</h2><p>Código: <strong>'.$codigoSeguro.'</strong></p><p>Validade: 10 minutos.</p>',
            'Seu novo código Zubbo: '.$novoCodigo.'. Validade: 10 minutos.');
    } catch (Throwable $e) {
        error_log('Falha ao reenviar código Zubbo: '.get_class($e));
        zubbo_rate_limit_hit('reenvio_codigo', 3600, $email);
        $_SESSION['erro_verificacao'] = 'Não foi possível enviar o e-mail. Confira o provedor configurado ou use o modo demo somente em ambiente local.';
        header('Location: verificar-email.php', true, 303);
        exit;
    }
}
$_SESSION['cadastro_pendente']['codigo'] = $novoCodigo;
$_SESSION['cadastro_pendente']['expiracao'] = $agora + 600;
$_SESSION['cadastro_pendente']['ultimo_envio'] = $agora;
$_SESSION['cadastro_pendente']['tentativas_codigo'] = 0;
if ($demo) $_SESSION['codigo_demo'] = $novoCodigo;
else unset($_SESSION['codigo_demo']);
zubbo_rate_limit_hit('reenvio_codigo', 3600, $email);
$_SESSION['sucesso_verificacao'] = 'Novo código gerado. Confira seu e-mail ou o código da demonstração.';
header('Location: verificar-email.php', true, 303);
exit;
