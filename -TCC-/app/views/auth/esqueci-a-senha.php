<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/mail.php';


$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (zubbo_rate_limit_exceeded('recuperacao_senha', 3, 900)) {
        $erro = 'Muitas solicitações. Aguarde alguns minutos e tente novamente.';
    } else {
        zubbo_rate_limit_hit('recuperacao_senha');
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Digite um e-mail válido.';
        } else {
            $stmt = $conn->prepare(
                "SELECT id_user, email_user, nome_user
                 FROM Usuario
                 WHERE email_user = ? AND status_user = 'ativo'
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $conn->prepare('DELETE FROM Recuperacao_Senha WHERE id_user = ?')
                    ->execute([$usuario['id_user']]);

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiracao = date('Y-m-d H:i:s', time() + 3600);

                $conn->prepare(
                    'INSERT INTO Recuperacao_Senha (id_user, token_hash, expiracao)
                     VALUES (?, ?, ?)'
                )->execute([$usuario['id_user'], $tokenHash, $expiracao]);

                $baseUrl = rtrim((string) (getenv('ZUBBO_BASE_URL') ?: 'http://localhost/-TCC-'), '/');
                $link = $baseUrl . '/public/reset-password.php?token=' . urlencode($token);

                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    zubbo_configurar_mail($mail);
                    $mail->addAddress($usuario['email_user'], $usuario['nome_user']);
                    $mail->Subject = 'Recuperação de senha - Zubbo';
                    $mail->isHTML(true);

                    $linkSeguro = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
                    $mail->Body = "<h2>Redefinição de senha</h2><p>Use o link abaixo para criar uma nova senha. Ele expira em 1 hora.</p><p><a href=\"{$linkSeguro}\">Redefinir minha senha</a></p>";
                    $mail->AltBody = "Redefina sua senha usando este link (válido por 1 hora): {$link}";
                    $mail->send();
                } catch (Throwable $e) {
                    error_log('Falha no e-mail de recuperação: ' . $e->getMessage());
                }
            }

            $mensagem = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';
        }
    }
}

include __DIR__ . '/../includes/head.php';
?>
<main class="login-container">
    <button class="btn-voltar" onclick="window.location.href='/../-TCC-/public/index.php'">←</button>
    <h1>Esqueci minha senha</h1>
    <p class="login-subtitle">Digite seu e-mail para receber o link de recuperação.</p>

    <?php if ($erro): ?><div class="alert erro-esqueci-senha"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <?php if ($mensagem): ?><div class="alert sucesso-esqueci-senha"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>

    <form method="POST">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required autocomplete="email">
        <button class="btn-esqueci-senha" type="submit">Enviar link de recuperação</button>
    </form>

    <a href="login.php" class="voltar-esqueci-senha">← Voltar para o login</a>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
