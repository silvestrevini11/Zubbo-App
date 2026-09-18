<?php
include __DIR__.'/../includes/head.php';
?>
<?php
session_start();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } else {
        /*
         * Aqui você deve consultar o banco de dados.
         *
         * Exemplo:
         * SELECT id, email FROM usuarios WHERE email = ?
         *
         * Depois, se o usuário existir:
         * 1. Gere um token aleatório.
         * 2. Salve o hash do token no banco.
         * 3. Defina uma validade, por exemplo, 1 hora.
         * 4. Envie o link por e-mail.
         */

        $token = bin2hex(random_bytes(32));

        // Exemplo de link que será enviado por e-mail
        $link = "https://seusite.com/reset-password.php?token=" . $token;

        /*
         * Aqui entraria o envio do e-mail.
         *
         * Recomendo usar PHPMailer em produção em vez de mail().
         */

        $mensagem = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="esqueci-senha-body">

<div class="container-esqueci-senha">
    <div class="card-esqueci-senha">

        <div class="icon-esqueci-senha"><svg class="svg-lock" fill="#ff4b1f" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M17,9V7c0-2.8-2.2-5-5-5S7,4.2,7,7v2c-1.7,0-3,1.3-3,3v7c0,1.7,1.3,3,3,3h10c1.7,0,3-1.3,3-3v-7C20,10.3,18.7,9,17,9z M9,7c0-1.7,1.3-3,3-3s3,1.3,3,3v2H9V7z M13.1,15.5c0,0-0.1,0.1-0.1,0.1V17c0,0.6-0.4,1-1,1s-1-0.4-1-1v-1.4c-0.6-0.6-0.7-1.5-0.1-2.1c0.6-0.6,1.5-0.7,2.1-0.1C13.6,13.9,13.7,14.9,13.1,15.5z"></path></g></svg></div>

        <h1>Esqueci minha senha</h1>

        <p class="descricao-esqueci-senha">
            Digite seu e-mail e enviaremos um link para você criar uma nova senha.
        </p>

        <?php if ($erro): ?>
            <div class="alert erro-esqueci-senha">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="alert sucesso-esqueci-senha">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label for="email">E-mail</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="seuemail@exemplo.com"
                required
                autocomplete="email"
            >

            <button class="btn-esqueci-senha" type="submit">
                Enviar link de recuperação
            </button>

        </form>

        <a href="login.php" class="voltar-esqueci-senha">
            ← Voltar para o login
        </a>

    </div>
</div>

</body>
</html>

<?php
include __DIR__.'/../includes/footer.php';
?>