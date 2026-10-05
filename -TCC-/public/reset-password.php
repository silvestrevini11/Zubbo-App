<?php
require_once __DIR__ . '/../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../config/database.php';

$erro = '';
$sucesso = '';
$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));

function carregarRecuperacao(PDO $conn, string $tokenHash, bool $bloquear = false): ?array
{
    $sql = "
        SELECT r.id_recuperacao, r.id_user, u.email_user
        FROM Recuperacao_Senha r
        INNER JOIN Usuario u ON u.id_user = r.id_user
        WHERE r.token_hash = ?
          AND r.usado = FALSE
          AND r.expiracao >= UTC_TIMESTAMP()
          AND u.status_user = 'ativo'
        LIMIT 1
    ";

    if ($bloquear) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $erro = 'Link de recuperação inválido ou expirado.';
} else {
    $tokenHash = hash('sha256', $token);

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            zubbo_require_csrf();

            $novaSenha = (string) ($_POST['nova_senha'] ?? '');
            $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

            if (strlen($novaSenha) < 8 || strlen($novaSenha) > 255) {
                $erro = 'A senha deve ter entre 8 e 255 caracteres.';
            } elseif ($novaSenha !== $confirmarSenha) {
                $erro = 'As senhas não são iguais.';
            } else {
                $conn->beginTransaction();
                $recuperacao = carregarRecuperacao($conn, $tokenHash, true);

                if (!$recuperacao) {
                    $conn->rollBack();
                    $erro = 'Link de recuperação inválido ou expirado.';
                } else {
                    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

                    $conn->prepare('UPDATE Usuario SET senha_user = ? WHERE id_user = ?')
                        ->execute([$senhaHash, (int) $recuperacao['id_user']]);

                    $conn->prepare('UPDATE Recuperacao_Senha SET usado = TRUE WHERE id_user = ?')
                        ->execute([(int) $recuperacao['id_user']]);

                    $conn->commit();
                    session_regenerate_id(true);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $sucesso = 'Sua senha foi alterada com sucesso. Você já pode entrar com a nova senha.';
                }
            }
        } elseif (!carregarRecuperacao($conn, $tokenHash)) {
            $erro = 'Link de recuperação inválido ou expirado.';
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
            <h1>Redefinir senha</h1>

            <?php if ($erro): ?>
                <div class="alert erro-esqueci-senha" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
                <a class="btn-esqueci-senha recuperacao-link-botao" href="../app/views/auth/esqueci-a-senha.php">Solicitar novo link</a>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert sucesso-esqueci-senha" role="status"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
                <a class="btn-esqueci-senha recuperacao-link-botao" href="../app/views/auth/login.php">Ir para o login</a>
            <?php elseif ($erro === ''): ?>
                <p class="descricao-esqueci-senha">Digite e confirme sua nova senha.</p>

                <form method="post">
                    <?= zubbo_csrf_input() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <label for="nova_senha">Nova senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" required minlength="8" maxlength="255" autocomplete="new-password">

                    <label for="confirmar_senha" class="recuperacao-label-confirmacao">Confirmar nova senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8" maxlength="255" autocomplete="new-password">

                    <button class="btn-esqueci-senha" type="submit">Alterar senha</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
