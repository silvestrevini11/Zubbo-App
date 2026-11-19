<?php
require_once __DIR__ . '/../../middleware/auth.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405); exit('Método não permitido.');
}
zubbo_require_csrf();
$id=(int)$_SESSION['usuario']['id'];
$pendente=$_SESSION['alteracao_email']??null;
if (!is_array($pendente) || (int)($pendente['id_user']??0)!==$id) {
    header('Location: perfil-editar.php?erro=email_expirado',true,303);exit;
}
if (time()-(int)($pendente['ultimo_envio']??0)<60) {
    $_SESSION['erro_email_reenvio']='Aguarde 60 segundos antes de reenviar.';
    header('Location: confirmar-email.php',true,303);exit;
}
if (zubbo_rate_limit_exceeded('reenvio_novo_email',5,3600,(string)$id)) {
    $_SESSION['erro_email_reenvio']='Limite de reenvios atingido.';
    header('Location: confirmar-email.php',true,303);exit;
}
$codigo=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
if (!zubbo_demo_mode()) {
    try {
        require_once __DIR__ . '/../../../vendor/autoload.php';
        require_once __DIR__ . '/../../../config/mail.php';
        zubbo_enviar_email((string)$pendente['novo_email'], 'Novo código para alterar e-mail - Zubbo',
            '<p>Código: <strong>'.htmlspecialchars($codigo,ENT_QUOTES,'UTF-8').'</strong></p><p>Expira em 10 minutos.</p>',
            'Novo código Zubbo para alterar e-mail: '.$codigo.'. Expira em 10 minutos.');
    } catch (Throwable $e) {
        error_log('Falha no reenvio de alteração de e-mail: '.get_class($e));
        zubbo_rate_limit_hit('reenvio_novo_email',3600,(string)$id);
        $_SESSION['erro_email_reenvio']='Falha no envio. Confira a configuração do provedor.';
        header('Location: confirmar-email.php',true,303);exit;
    }
}
$_SESSION['alteracao_email']['codigo_hash']=hash('sha256',$codigo);
$_SESSION['alteracao_email']['expira_em']=time()+600;
$_SESSION['alteracao_email']['ultimo_envio']=time();
$_SESSION['alteracao_email']['tentativas']=0;
if (zubbo_demo_mode())$_SESSION['codigo_email_demo']=$codigo;
else unset($_SESSION['codigo_email_demo']);
zubbo_rate_limit_hit('reenvio_novo_email',3600,(string)$id);
$_SESSION['sucesso_email_reenvio']='Novo código enviado.';
header('Location: confirmar-email.php',true,303);exit;
