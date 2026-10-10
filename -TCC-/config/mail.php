<?php
declare(strict_types=1);

function zubbo_configurar_mail(\PHPMailer\PHPMailer\PHPMailer $mail): void
{
    $usuario = trim((string) getenv('ZUBBO_SMTP_USER'));
    $senha = (string) getenv('ZUBBO_SMTP_PASSWORD');
    $host = trim((string) (getenv('ZUBBO_SMTP_HOST') ?: 'smtp.gmail.com'));
    $porta = (int) (getenv('ZUBBO_SMTP_PORT') ?: 587);
    $remetente = trim((string) (getenv('ZUBBO_MAIL_FROM') ?: $usuario));
    $modo = strtolower(trim((string) (getenv('ZUBBO_SMTP_ENCRYPTION') ?: 'auto')));

    if ($usuario === '' || $senha === '' || $remetente === '') {
        throw new RuntimeException('SMTP não configurado no ambiente.');
    }

    if (!in_array($modo, ['auto', 'starttls', 'smtps', 'none'], true)) {
        throw new RuntimeException('ZUBBO_SMTP_ENCRYPTION inválido.');
    }

    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $usuario;
    $mail->Password = $senha;
    $mail->Port = $porta;
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 10;
    $mail->Timelimit = 15;

    if ($modo === 'auto') {
        $modo = $porta === 465 ? 'smtps' : 'starttls';
    }

    if ($modo === 'smtps') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($modo === 'starttls') {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPAutoTLS = false;
        $mail->SMTPSecure = '';
    }

    $mail->setFrom($remetente, 'Zubbo');
}

function zubbo_enviar_email(
    string $destinatario,
    string $assunto,
    string $html,
    string $texto = ''
): void {
    $provider = strtolower(trim((string) (getenv('ZUBBO_MAIL_PROVIDER') ?: 'resend')));

    if ($provider === 'resend') {
        zubbo_enviar_email_resend($destinatario, $assunto, $html, $texto);
        return;
    }

    if ($provider === 'smtp') {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        zubbo_configurar_mail($mail);
        $mail->addAddress($destinatario);
        $mail->Subject = $assunto;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $texto !== '' ? $texto : strip_tags($html);
        $mail->send();
        return;
    }

    throw new RuntimeException('ZUBBO_MAIL_PROVIDER inválido. Use resend ou smtp.');
}

function zubbo_enviar_email_resend(
    string $destinatario,
    string $assunto,
    string $html,
    string $texto = ''
): void {
    $apiKey = trim((string) getenv('ZUBBO_RESEND_API_KEY'));
    $remetente = trim((string) (getenv('ZUBBO_MAIL_FROM') ?: 'Zubbo <onboarding@resend.dev>'));

    if ($apiKey === '') {
        throw new RuntimeException('ZUBBO_RESEND_API_KEY não configurada.');
    }

    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Destinatário de e-mail inválido.');
    }

    $payload = [
        'from' => $remetente,
        'to' => [$destinatario],
        'subject' => $assunto,
        'html' => $html,
        'text' => $texto !== '' ? $texto : strip_tags($html),
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Falha ao montar o e-mail.');
    }

    $status = 0;
    $response = false;

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.resend.com/emails');
        if ($ch === false) {
            throw new RuntimeException('Não foi possível iniciar a conexão com a API de e-mail.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => $json,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Falha na API de e-mail: ' . $erroCurl);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 15,
                'ignore_errors' => true,
                'header' => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                'content' => $json,
            ],
        ]);

        $response = @file_get_contents('https://api.resend.com/emails', false, $context);

        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match)) {
                $status = (int) $match[1];
                break;
            }
        }
    }

    if ($status < 200 || $status >= 300) {
        $detalhe = is_string($response) ? trim($response) : '';
        throw new RuntimeException(
            'API de e-mail retornou HTTP ' . $status . ($detalhe !== '' ? ': ' . $detalhe : '')
        );
    }
}
