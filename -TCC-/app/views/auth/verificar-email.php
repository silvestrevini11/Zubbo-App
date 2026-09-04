<?php

session_start();

/*
    Quando o cadastro for concluído,
    vamos guardar o ID do usuário nesta sessão:

    $_SESSION['usuario_verificacao']
*/

if (!isset($_SESSION['usuario_verificacao'])) {
    header('Location: cadastro.php');
    exit;
}

$erro = $_SESSION['erro_verificacao'] ?? null;

unset($_SESSION['erro_verificacao']);

?>

<main class="verificacao-container">

    <h1>Verifique seu e-mail</h1>

    <p>
        Enviamos um código de 6 dígitos para o seu e-mail.
        Digite o código abaixo para confirmar sua conta.
    </p>

    <?php if ($erro): ?>

        <p class="mensagem-erro">
            <?= htmlspecialchars($erro) ?>
        </p>

    <?php endif; ?>

    <form action="processa-verificacao.php" method="POST">

        <label for="codigo">
            Código de verificação
        </label>

        <input
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

        <button type="submit">
            Verificar e-mail
        </button>

    </form>

</main> 