<?php
require_once __DIR__ . '/../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../config/database.php';

$erro = '';
$sucesso = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$recuperacao = null;

if ($token === '') {
    $erro = 'Link de recuperação inválido.';
} else {
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare(
        "SELECT r.id_recuperacao, r.id_user, r.expiracao, r.usado
         FROM Recuperacao_Senha r
         INNER JOIN Usuario u ON u.id_user = r.id_user
         WHERE r.token_hash = ? AND u.status_user = 'ativo'
         LIMIT 1"
    );
    $stmt->execute([$tokenHash]);
    $recuperacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recuperacao || (int) $recuperacao['usado'] === 1 || strtotime($recuperacao['expiracao']) < time()) {
        $erro = 'Link de recuperação inválido ou expirado.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (strlen($novaSenha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = 'As senhas não são iguais.';
    } else {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare(
                'UPDATE Recuperacao_Senha
                 SET usado = TRUE
                 WHERE id_recuperacao = ? AND usado = FALSE AND expiracao >= NOW()'
            );
            $stmt->execute([$recuperacao['id_recuperacao']]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Token inválido.');
            }

            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $conn->prepare('UPDATE Usuario SET senha_user = ? WHERE id_user = ?')
                ->execute([$senhaHash, $recuperacao['id_user']]);

            $conn->prepare('UPDATE Recuperacao_Senha SET usado = TRUE WHERE id_user = ?')
                ->execute([$recuperacao['id_user']]);

            $conn->commit();
            session_regenerate_id(true);
            $sucesso = 'Sua senha foi alterada com sucesso.';
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Falha ao redefinir senha: ' . $e->getMessage());
            $erro = 'Não foi possível alterar a senha. Solicite um novo link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="esqueci-senha-body">
<div class="container-esqueci-senha"><div class="card-esqueci-senha">
    <h1>Redefinir senha</h1>
    <?php if ($erro): ?><div class="alert erro-esqueci-senha"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?>
        <div class="alert sucesso-esqueci-senha"><?= htmlspecialchars($sucesso) ?></div>
        <a href="../app/views/auth/login.php" class="btn-esqueci-senha">Ir para o login</a>
    <?php elseif ($erro === ''): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <label for="nova_senha">Nova senha</label>
            <input type="password" id="nova_senha" name="nova_senha" required minlength="8" autocomplete="new-password">
            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8" autocomplete="new-password">
            <button class="btn-esqueci-senha" type="submit">Alterar senha</button>
        </form>
    <?php endif; ?>
</div></div>
</body>
</html>
