<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

$erro = $_SESSION['erro_login'] ?? null;
unset($_SESSION['erro_login']);

include __DIR__ . '/../includes/head.php';
?>
<main class="login-container">
    <button class="btn-voltar" type="button" onclick="window.location.href='../../../public/index.php'">←</button>

    <img src="../../../public/imagem/LogooZ.png" alt="Logo Zubbo" class="login-logo">
    <h1>Entrar</h1>
    <p class="login-subtitle">Que bom te ver de novo!</p>

    <form action="processa-login.php" method="post">
        <input type="email" name="email" placeholder="Email" autocomplete="email" required>
        <input type="password" name="password" placeholder="Senha" autocomplete="current-password" required>

        <a href="esqueci-a-senha.php" class="login-forgot-password">Esqueceu sua senha?</a>
        <button type="submit" class="login-button">Entrar</button>
    </form>

    <?php if ($erro): ?>
        <div class="alert erro-esqueci-senha" role="alert">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
