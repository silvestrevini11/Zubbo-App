<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

$erro = $_SESSION['erro_login'] ?? null;
unset($_SESSION['erro_login']);

if (isset($_GET['conta']) && $_GET['conta'] === 'inativa') {
    $erro = 'Esta conta não está disponível para acesso.';
}

include __DIR__ . '/../includes/head.php';
?>
<main class="login-container">
    <button class="btn-voltar" type="button" onclick="window.location.href='<?= htmlspecialchars(zubbo_url('/public/index.php'), ENT_QUOTES, 'UTF-8') ?>'">←</button>

    <img src="<?= htmlspecialchars(zubbo_url('/public/imagem/LogooZ.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo Zubbo" class="login-logo">
    <h1>Entrar</h1>
    <p class="login-subtitle">Que bom te ver de novo!</p>

    <form action="processa-login.php" method="post">
        <?= zubbo_csrf_input() ?>
        <input type="email" name="email" placeholder="Email" autocomplete="email" required>
        <input type="password" name="password" placeholder="Senha" autocomplete="current-password" required>

        <a href="esqueci-a-senha.php" class="login-forgot-password">Esqueceu sua senha?</a>
        <button type="submit" class="login-button">Entrar</button>
    </form>

    <?php if ($erro): ?>
        <div class="alert erro-esqueci-senha" role="alert">
            <?= htmlspecialchars((string) $erro, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
