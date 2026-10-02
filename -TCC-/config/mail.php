<?php
declare(strict_types=1);

function zubbo_configurar_mail(\PHPMailer\PHPMailer\PHPMailer $mail): void
{
    $usuario = trim((string) getenv('ZUBBO_SMTP_USER'));
    $senha = (string) getenv('ZUBBO_SMTP_PASSWORD');
    $host = trim((string) (getenv('ZUBBO_SMTP_HOST') ?: 'smtp.gmail.com'));
    $porta = (int) (getenv('ZUBBO_SMTP_PORT') ?: 587);
    $remetente = trim((string) (getenv('ZUBBO_MAIL_FROM') ?: $usuario));

    if ($usuario === '' || $senha === '' || $remetente === '') {
        throw new RuntimeException('SMTP não configurado no ambiente.');
    }

    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $usuario;
    $mail->Password = $senha;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $porta;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($remetente, 'Zubbo');
}
