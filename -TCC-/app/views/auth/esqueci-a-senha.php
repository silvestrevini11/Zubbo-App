<?php
session_start();

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensagem = '';
$erro = '';
$linkTeste = null;

if (empty($_SESSION['csrf_recuperacao'])) {
    $_SESSION['csrf_recuperacao'] = bin2hex(random_bytes(32));
}

function urlBaseZubbo(): string
{
    $configurada = trim((string) getenv('ZUBBO_APP_URL'));
    if ($configurada !== '') {
        return rtrim($configurada, '/');
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostSemPorta = explode(':', $host)[0];

    if (!in_array($hostSemPorta, ['localhost', '127.0.0.1'], true)) {
        return '';
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = preg_replace('#/app/views/auth/[^/]+$#', '', $script);

    if (!is_string($base) || $base === $script) {
        return '';
    }

    return 'http://' . $host . rtrim($base, '/');
}

function enviarEmailRecuperacao(string $email, string $link): bool
{
    $smtpUser = trim((string) getenv('ZUBBO_SMTP_USER'));
    $smtpPass = (string) getenv('ZUBBO_SMTP_PASS');

    if ($smtpUser === '' || $smtpPass === '') {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = trim((string) (getenv('ZUBBO_SMTP_HOST') ?: 'smtp.gmail.com'));
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) (getenv('ZUBBO_SMTP_PORT') ?: 587);
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($smtpUser, 'Zubbo');
        $mail->addAddress($email);
        $mail->Subject = 'Redefinição de senha - Zubbo';
        $mail->isHTML(true);

        $linkSeguro = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $mail->Body = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:30px'>
                <h2 style='color:#222'>Redefina sua senha</h2>
                <p>Recebemos uma solicitação para alterar a senha da sua conta Zubbo.</p>
                <p>
                    <a href='{$linkSeguro}' style='display:inline-block;padding:12px 18px;background:#df421d;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold'>
                        Criar nova senha
                    </a>
                </p>
                <p>Este link é válido por <strong>1 hora</strong> e só pode ser usado uma vez.</p>
                <p>Se você não solicitou a alteração, ignore este e-mail.</p>
            </div>
        ";

        $mail->AltBody =
            "Recebemos uma solicitação para redefinir sua senha no Zubbo.\n\n" .
            "Abra este link: {$link}\n\n" .
            "O link é válido por 1 hora e só pode ser usado uma vez.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar recuperação de senha: ' . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $email = trim((string) ($_POST['email'] ?? ''));

    if (!is_string($csrf) || !hash_equals($_SESSION['csrf_recuperacao'], $csrf)) {
        http_response_code(403);
        $erro = 'A sessão do formulário expirou. Atualize a página e tente novamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } else {
        $mensagem = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';

        try {
            $stmt = $conn->prepare("
                SELECT id_user, email_user
                FROM Usuario
                WHERE email_user = ?
                  AND status_user = 'ativo'
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiracao = date('Y-m-d H:i:s', time() + 3600);

                $conn->beginTransaction();

                $stmt = $conn->prepare('DELETE FROM Recuperacao_Senha WHERE id_user = ?');
                $stmt->execute([(int) $usuario['id_user']]);

                $stmt = $conn->prepare("
                    INSERT INTO Recuperacao_Senha (id_user, token_hash, expiracao)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    (int) $usuario['id_user'],
                    $tokenHash,
                    $expiracao
                ]);

                $conn->commit();

                $baseUrl = urlBaseZubbo();

                if ($baseUrl !== '') {
                    $link = $baseUrl . '/public/reset-password.php?token=' . urlencode($token);
                    $enviado = enviarEmailRecuperacao($usuario['email_user'], $link);

                    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
                    $hostSemPorta = explode(':', $host)[0];

                    if (!$enviado && in_array($hostSemPorta, ['localhost', '127.0.0.1'], true)) {
                        $linkTeste = $link;
                    }
                } else {
                    error_log('ZUBBO_APP_URL não configurada para recuperação de senha.');
                }
            }
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

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
                <path d="M17,9V7c0-2.8-2.2-5-5-5S7,4.2,7,7v2c-1.7,0-3,1.3-3,3v7c0,1.7,1.3,3,3,3h10c1.7,0,3-1.3,3-3v-7C20,10.3,18.7,9,17,9z M9,7c0-1.7,1.3-3,3-3s3,1.3,3,3v2H9V7z M13.1,15.5c0,0-0.1,0.1-0.1,0.1V17c0,0.6-0.4,1-1,1s-1-.4-1-1v-1.4c-.6-.6-.7-1.5-.1-2.1.6-.6,1.5-.7,2.1-.1.6.5.7,1.5.1,2.1z"></path>
            </svg>
        </div>

        <h1>Esqueci minha senha</h1>

        <p class="descricao-esqueci-senha">
            Digite seu e-mail e enviaremos um link para você criar uma nova senha.
        </p>

        <?php if ($erro): ?>
            <div class="alert erro-esqueci-senha" role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="alert sucesso-esqueci-senha" role="status">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_recuperacao'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="email">E-mail</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="seuemail@exemplo.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
                autocomplete="email"
            >

            <button class="btn-esqueci-senha" type="submit">
                Enviar link de recuperação
            </button>
        </form>

        <a href="login.php" class="voltar-esqueci-senha">
            ← Voltar para o login
        </a>

        <?php if ($linkTeste): ?>
            <div class="recuperacao-link-teste">
                <strong>Teste local</strong>
                <span>SMTP não configurado. Use este link somente durante o desenvolvimento:</span>
                <a href="<?= htmlspecialchars($linkTeste, ENT_QUOTES, 'UTF-8') ?>">
                    Abrir redefinição de senha
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
