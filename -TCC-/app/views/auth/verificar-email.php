<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (empty($_SESSION['cadastro_pendente']) || !is_array($_SESSION['cadastro_pendente'])) {
    header('Location: cadastro.php');
    exit;
}

include __DIR__ . '/../includes/head.php';

$erro = $_SESSION['erro_verificacao'] ?? null;
$sucesso = $_SESSION['sucesso_verificacao'] ?? null;
unset($_SESSION['sucesso_verificacao']);
unset($_SESSION['erro_verificacao']);

$demoMode = zubbo_demo_mode();
$codigoDemo = $demoMode ? (string) ($_SESSION['codigo_demo'] ?? '') : '';
?>
<main class="verificar-container">
    <h1 class="verificar-titulo">Verifique seu e-mail</h1>

    <p class="verificar-text">
        <?php if ($demoMode): ?>
            Ambiente de demonstração: use o código abaixo para confirmar sua conta.
        <?php else: ?>
            Enviamos um código de 6 dígitos para o seu e-mail.
            Digite o código abaixo para confirmar sua conta.
        <?php endif; ?>
    </p>

    <?php if ($demoMode && $codigoDemo !== ''): ?>
        <div class="alert sucesso-esqueci-senha" role="status" style="margin-bottom: 20px; text-align: center;">
            <strong>Modo demonstração</strong><br>
            Código: <strong><?= htmlspecialchars($codigoDemo, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <p class="alert sucesso-esqueci-senha" role="status"><?= htmlspecialchars((string)$sucesso, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
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
    <form action="reenviar-codigo.php" method="post" style="margin-top:14px;text-align:center">
        <?= zubbo_csrf_input() ?>
        <button type="submit" class="verificar-btn">Reenviar código</button>
        <p>Você pode solicitar um novo código após 60 segundos.</p>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
