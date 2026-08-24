<?php
session_start();
include __DIR__.'/../includes/head.php';
?>

<main class="login-container">

<button class="btn-voltar" onclick="history.back()">
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

        <a href="#" class="login-forgot-password">
            Esqueceu sua senha?
        </a>

        <button type="submit" class="login-button">
            Entrar
        </button>

    </form>

    <div class="login-divider">
        <span></span>
        <strong>ou</strong>
        <span></span>
    </div>

    <button class="login-social-button facebook">
        <span class="login-facebook-icon">f</span>
        <span>Continuar com Facebook</span>
    </button>

    <button class="login-social-button google">
        <span class="login-google-icon">G</span>
        <span>Continuar com Google</span>
    </button>

    <button class="login-social-button apple">
        <span class="login-apple-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="img" aria-label="Apple">
                <path d="M16.7 12.1c0-2.1 1.7-3.1 1.8-3.2-1-1.4-2.5-1.6-3-1.6-1.3-.1-2.5.8-3.1.8-.6 0-1.6-.8-2.7-.8-1.4 0-2.7.8-3.4 2.1-1.5 2.5-.4 6.3 1 8.3.7 1 1.5 2.1 2.6 2 .9 0 1.3-.6 2.5-.6s1.5.6 2.5.6c1.1 0 1.8-1 2.5-2 .8-1.1 1.1-2.2 1.1-2.3-.1 0-2.1-.8-2.1-3.3ZM14.7 6.1c.6-.8 1-1.8.9-2.9-.9 0-2 .6-2.6 1.4-.6.7-1.1 1.7-1 2.7 1 .1 2-.5 2.7-1.2Z" />
            </svg>
        </span>
        <span>Continuar com Apple</span>
    </button>

</main>

<?php
include __DIR__.'/../includes/footer.php';
?>
