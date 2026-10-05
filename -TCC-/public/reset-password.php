<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$erro = '';
$sucesso = '';
$recuperacao = null;

if (empty($_SESSION['csrf_reset_senha'])) {
    $_SESSION['csrf_reset_senha'] = bin2hex(random_bytes(32));
}

$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));

function carregarRecuperacao(PDO $conn, string $tokenHash, bool $bloquear = false)
{
    $sql = "
        SELECT r.id_recuperacao, r.id_user, r.expiracao, r.usado, u.email_user
        FROM Recuperacao_Senha r
        INNER JOIN Usuario u ON u.id_user = r.id_user
        WHERE r.token_hash = ?
        LIMIT 1
    ";

    if ($bloquear) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([$tokenHash]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function validarRecuperacao($recuperacao): string
{
    if (!$recuperacao) {
        return 'Link de recuperação inválido ou inexistente.';
    }

    if ((int) $recuperacao['usado'] === 1) {
        return 'Este link de recuperação já foi utilizado.';
    }

    if (strtotime($recuperacao['expiracao']) < time()) {
        return 'Este link de recuperação expirou. Solicite um novo link.';
    }

    return '';
}

if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $erro = 'Link de recuperação inválido.';
} else {
    $tokenHash = hash('sha256', $token);

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            $novaSenha = (string) ($_POST['nova_senha'] ?? '');
            $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

            if (!is_string($csrf) || !hash_equals($_SESSION['csrf_reset_senha'], $csrf)) {
                http_response_code(403);
                $erro = 'A sessão do formulário expirou. Abra novamente o link de recuperação.';
            } elseif (strlen($novaSenha) < 8 || strlen($novaSenha) > 255) {
                $erro = 'A senha deve ter entre 8 e 255 caracteres.';
            } elseif ($novaSenha !== $confirmarSenha) {
                $erro = 'As senhas não são iguais.';
            } else {
                $conn->beginTransaction();

                $recuperacao = carregarRecuperacao($conn, $tokenHash, true);
                $erro = validarRecuperacao($recuperacao);

                if ($erro !== '') {
                    $conn->rollBack();
                } else {
                    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare('UPDATE Usuario SET senha_user = ? WHERE id_user = ?');
                    $stmt->execute([$senhaHash, (int) $recuperacao['id_user']]);

                    $stmt = $conn->prepare('UPDATE Recuperacao_Senha SET usado = TRUE WHERE id_user = ?');
                    $stmt->execute([(int) $recuperacao['id_user']]);

                    $conn->commit();

                    unset($_SESSION['csrf_reset_senha']);
                    $sucesso = 'Sua senha foi alterada com sucesso. Você já pode entrar com a nova senha.';
                }
            }
        } else {
            $recuperacao = carregarRecuperacao($conn, $tokenHash);
            $erro = validarRecuperacao($recuperacao);
        }
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        error_log('Erro ao redefinir senha: ' . $e->getMessage());
        $erro = 'Não foi possível redefinir a senha agora. Solicite um novo link e tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        if (localStorage.getItem('zubbo-tema') === 'escuro') {
            document.documentElement.classList.add('tema-escuro');
        }
    </script>
    <title>Redefinir senha | Zubbo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="esqueci-senha-body">
    <main class="container-esqueci-senha">
        <div class="card-esqueci-senha">
            <div class="icon-esqueci-senha" aria-hidden="true">
                <svg class="svg-lock" fill="#ff4b1f" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17,9V7c0-2.8-2.2-5-5-5S7,4.2,7,7v2c-1.7,0-3,1.3-3,3v7c0,1.7,1.3,3,3,3h10c1.7,0,3-1.3,3-3v-7C20,10.3,18.7,9,17,9z M9,7c0-1.7,1.3-3,3-3s3,1.3,3,3v2H9V7z M13.1,15.5c0,0-0.1,0.1-0.1,0.1V17c0,.6-.4,1-1,1s-1-.4-1-1v-1.4c-.6-.6-.7-1.5-.1-2.1.6-.6,1.5-.7,2.1-.1.6.5.7,1.5.1,2.1z"></path>
                </svg>
            </div>

            <h1>Redefinir senha</h1>

            <?php if ($erro): ?>
                <div class="alert erro-esqueci-senha" role="alert">
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <a class="btn-esqueci-senha recuperacao-link-botao" href="../app/views/auth/esqueci-a-senha.php">
                    Solicitar novo link
                </a>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert sucesso-esqueci-senha" role="status">
                    <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <a class="btn-esqueci-senha recuperacao-link-botao" href="../app/views/auth/login.php">
                    Ir para o login
                </a>
            <?php elseif ($erro === ''): ?>
                <p class="descricao-esqueci-senha">
                    Digite e confirme sua nova senha.
                </p>

                <form method="post">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_reset_senha'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <input
                        type="hidden"
                        name="token"
                        value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <label for="nova_senha">Nova senha</label>
                    <input
                        type="password"
                        id="nova_senha"
                        name="nova_senha"
                        placeholder="Mínimo de 8 caracteres"
                        required
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                    >

                    <label for="confirmar_senha" class="recuperacao-label-confirmacao">
                        Confirmar nova senha
                    </label>
                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        placeholder="Digite a senha novamente"
                        required
                        minlength="8"
                        maxlength="255"
                        autocomplete="new-password"
                    >

                    <button class="btn-esqueci-senha" type="submit">
                        Alterar senha
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>