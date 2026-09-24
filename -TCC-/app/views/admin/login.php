<?php
session_start();

if (!empty($_SESSION['admin']['id'])) {
    header('Location: painel.php');
    exit;
}

$erro = $_SESSION['admin_erro_login'] ?? null;
unset($_SESSION['admin_erro_login']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área administrativa | Zubbo</title>
    <link rel="stylesheet" href="../../../public/css/admin.css">
</head>
<body class="admin-login-body">
<main class="admin-login-card">
    <a class="admin-login-voltar" href="../auth/login.php">← Voltar ao login</a>
    <img src="../../../public/imagem/LogooZ.png" alt="Zubbo" class="admin-login-logo">
    <p class="admin-login-kicker">ACESSO RESTRITO</p>
    <h1>Painel administrativo</h1>
    <p class="admin-login-sub">Entre com uma conta cadastrada na tabela de administradores.</p>

    <?php if ($erro): ?>
        <div class="admin-login-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form action="processa-login.php" method="post" class="admin-login-form">
        <label for="admin-email">E-mail</label>
        <input id="admin-email" type="email" name="email" autocomplete="username" required placeholder="admin@zubbo.com">

        <label for="admin-senha">Senha</label>
        <input id="admin-senha" type="password" name="password" autocomplete="current-password" required placeholder="Sua senha">

        <button type="submit">Entrar no painel</button>
    </form>

    <p class="admin-login-aviso">O acesso e as ações administrativas ficam separados das contas comuns.</p>
</main>
</body>
</html>
