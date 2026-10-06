<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (empty($_SESSION['cadastro_pendente']) || !is_array($_SESSION['cadastro_pendente'])) {
    header('Location: cadastro.php');
    exit;
}

include __DIR__ . '/../includes/head.php';

$erro = $_SESSION['erro_verificacao'] ?? null;
unset($_SESSION['erro_verificacao']);
?>
<main class="verificar-container">
    <h1 class="verificar-titulo">Verifique seu e-mail</h1>

    <p class="verificar-text">
        Enviamos um código de 6 dígitos para o seu e-mail.
        Digite o código abaixo para confirmar sua conta.
    </p>

    <?php if ($erro): ?>
        <p class="verificar-mensagem-erro">
            <?= htmlspecialchars((string) $erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form class="verificar-form" action="processa-verificacao.php" method="POST">
        <?= zubbo_csrf_input() ?>

        <input
            class="verificar-caixa"
            type="text"
            id="codigo"
            name="codigo"
            maxlength="6"
            minlength="6"
            inputmode="numeric"
            pattern="[0-9]{6}"
            autocomplete="one-time-code"
            placeholder="000000"
            required
        >

        <button type="submit" class="verificar-btn">Verificar</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
