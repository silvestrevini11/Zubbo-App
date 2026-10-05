<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/mail.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    zubbo_require_csrf();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $mensagem = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
        $mensagem = '';
    } elseif (zubbo_rate_limit_exceeded('recuperacao_senha', 4, 3600, $email)) {
        $erro = 'Muitas tentativas. Aguarde antes de solicitar outro link.';
        $mensagem = '';
    } else {
        zubbo_rate_limit_hit('recuperacao_senha', 3600, $email);

        try {
            $stmt = $conn->prepare(
                "SELECT id_user, email_user
                 FROM Usuario
                 WHERE email_user = ? AND status_user = 'ativo'
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                $stmt = $conn->prepare(
                    'INSERT INTO Recuperacao_Senha (id_user, token_hash, expiracao)
                     VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR))'
                );
                $stmt->execute([(int) $usuario['id_user'], $tokenHash]);
                $novoId = (int) $conn->lastInsertId();

                try {
                    $link = zubbo_absolute_url('/public/reset-password.php?token=' . urlencode($token));

                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    zubbo_configurar_mail($mail);
                    $mail->addAddress((string) $usuario['email_user']);
                    $mail->Subject = 'Redefinição de senha - Zubbo';
                    $mail->isHTML(true);

                    $linkSeguro = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
                    $mail->Body =
                        '<h2>Redefina sua senha</h2>' .
                        '<p>Use o link abaixo para criar uma nova senha. Ele expira em 1 hora.</p>' .
                        '<p><a href="' . $linkSeguro . '">Redefinir minha senha</a></p>';
                    $mail->AltBody = 'Redefina sua senha usando este link (válido por 1 hora): ' . $link;
                    $mail->send();

                    $conn->prepare(
                        'DELETE FROM Recuperacao_Senha
                         WHERE id_user = ? AND id_recuperacao <> ?'
                    )->execute([(int) $usuario['id_user'], $novoId]);
                } catch (Throwable $mailError) {
                    $conn->prepare('DELETE FROM Recuperacao_Senha WHERE id_recuperacao = ?')
                        ->execute([$novoId]);
                    error_log('Falha no e-mail de recuperação: ' . $mailError->getMessage());
                }
            }
        } catch (Throwable $e) {
            error_log('Erro na recuperação de senha: ' . $e->getMessage());
            $erro = 'Não foi possível processar a recuperação agora. Tente novamente.';
            $mensagem = '';
        }
    }
}

include __DIR__ . '/../includes/head.php';
?>
<main class="container-esqueci-senha">
    <button class="btn-voltar" type="button" onclick="window.location.href='login.php'">←</button>

    <div class="card-esqueci-senha">
        <div class="icon-esqueci-senha" aria-hidden="true">
            <svg class="svg-lock" fill="#ff4b1f" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M17,9V7c0-2.8-2.2-5-5-5S7,4.2,7,7v2c-1.7,0-3,1.3-3,3v7c0,1.7,1.3,3,3,3h10c1.7,0,3-1.3,3-3v-7C20,10.3,18.7,9,17,9z M9,7c0-1.7,1.3-3,3-3s3,1.3,3,3v2H9V7z"></path>
            </svg>
        </div>

        <h1>Esqueci minha senha</h1>
        <p class="descricao-esqueci-senha">Digite seu e-mail e enviaremos um link para você criar uma nova senha.</p>

        <?php if ($erro): ?>
            <div class="alert erro-esqueci-senha" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="alert sucesso-esqueci-senha" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post">
            <?= zubbo_csrf_input() ?>
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="seuemail@exemplo.com"
                   value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   required autocomplete="email">
            <button class="btn-esqueci-senha" type="submit">Enviar link de recuperação</button>
        </form>

        <a href="login.php" class="voltar-esqueci-senha">← Voltar para o login</a>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
