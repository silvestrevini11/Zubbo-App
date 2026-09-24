<?php
session_start();

$erro = $_SESSION['erro_login'] ?? null;

unset($_SESSION['erro_login']);

include __DIR__.'/../includes/head.php';
?>

<main class="login-container">

<button class="btn-voltar" onclick="window.location.href='/../-TCC-/public/index.php'">
    ←
</button>

    <img src="../../../public/imagem/LogooZ.png" alt="Logo Zubbo" class="login-logo">

    <h1>Entrar</h1>

    <p class="login-subtitle">Que bom te ver de novo!</p>

    <form action="processa-login.php" method="POST">

        <input 
            type="email" 
            name="email"
            placeholder="Email"
            required
        >

        <input 
            type="password" 
            name="password"
            placeholder="Senha"
            required
        >

        <a href="esqueci-a-senha.php" class="login-forgot-password">
            Esqueceu sua senha?
        </a>

        <button type="submit" class="login-button">
            Entrar
        </button>

    </form>

    <?php if ($erro): ?>

        <script>
            alert("<?= htmlspecialchars($erro) ?>");
        </script>

    <?php endif; ?>

</main>

<?php
include __DIR__.'/../includes/footer.php';
?>