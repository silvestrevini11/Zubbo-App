<?php

session_start();

require_once __DIR__.'/../../../config/database.php';
include __DIR__ . '/../includes/head.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Digite um e-mail válido.';

    } else {

        /*
         * Procura o usuário pelo e-mail
         */
        $sql = "SELECT id_user, email_user
                FROM Usuario
                WHERE email_user = :email
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':email' => $email
        ]);

        $usuario = $stmt->fetch();

        /*
         * Mesmo que o usuário não exista,
         * mostramos a mesma mensagem.
         *
         * Isso evita revelar quais e-mails
         * estão cadastrados no sistema.
         */
        if ($usuario) {

            /*
             * Remove tokens antigos desse usuário
             */
            $sqlDelete = "DELETE FROM Recuperacao_Senha
                          WHERE id_user = :id_user";

            $stmtDelete = $conn->prepare($sqlDelete);

            $stmtDelete->execute([
                ':id_user' => $usuario['id_user']
            ]);

            /*
             * Gera token seguro
             */
            $token = bin2hex(random_bytes(32));

            /*
             * Salva somente o HASH do token
             */
            $tokenHash = hash('sha256', $token);

            /*
             * Token válido por 1 hora
             */
            $expiracao = date(
                'Y-m-d H:i:s',
                time() + 3600
            );

            /*
             * Salva no banco
             */
            $sqlInsert = "INSERT INTO Recuperacao_Senha
                          (
                              id_user,
                              token_hash,
                              expiracao
                          )
                          VALUES
                          (
                              :id_user,
                              :token_hash,
                              :expiracao
                          )";

            $stmtInsert = $conn->prepare($sqlInsert);

            $stmtInsert->execute([
                ':id_user' => $usuario['id_user'],
                ':token_hash' => $tokenHash,
                ':expiracao' => $expiracao
            ]);

            /*
             * Link para redefinir a senha
             *
             * ALTERE esse endereço para o endereço
             * real do seu projeto.
             */
            $link = "http://localhost/-TCC-/public/reset-password.php?token=" . urlencode($token);

            /*
             * POR ENQUANTO:
             * mostramos o link para teste.
             *
             * Depois vamos substituir isso pelo
             * envio real através do PHPMailer.
             */
            $mensagem = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';

            /*
             * APENAS PARA TESTE LOCAL
             *
             * Remova depois que configurarmos o e-mail.
             */
            $linkTeste = $link;
        }
        else {

            $mensagem = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Esqueci minha senha</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>

<body class="esqueci-senha-body">

    <button
        class="btn-voltar"
        onclick="window.location.href='/../-TCC-/public/index.php'"
    >
        ←
    </button>

    <div class="container-esqueci-senha">

        <div class="card-esqueci-senha">

            <div class="icon-esqueci-senha">

                <svg
                    class="svg-lock"
                    fill="#ff4b1f"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >

                    <path
                        d="M17,9V7c0-2.8-2.2-5-5-5S7,4.2,7,7v2c-1.7,0-3,1.3-3,3v7c0,1.7,1.3,3,3,3h10c1.7,0,3-1.3,3-3v-7C20,10.3,18.7,9,17,9z M9,7c0-1.7,1.3-3,3-3s3,1.3,3,3v2H9V7z M13.1,15.5c0,0-0.1,0.1-0.1,0.1V17c0,0.6-0.4,1-1,1s-1-0.4-1-1v-1.4c-0.6-0.6-0.7-1.5-0.1-2.1c0.6-0.6,1.5-0.7,2.1-0.1C13.6,13.9,13.7,14.9,13.1,15.5z"
                    >

                    </path>

                </svg>

            </div>

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

                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="seuemail@exemplo.com"
                    required
                    autocomplete="email"
                >

                <button
                    class="btn-esqueci-senha"
                    type="submit"
                >
                    Enviar link de recuperação
                </button>

            </form>

            <a
                href="login.php"
                class="voltar-esqueci-senha"
            >
                ← Voltar para o login
            </a>



            <?php if (isset($linkTeste)): ?>

                <div style="
                    margin-top: 20px;
                    padding: 12px;
                    background: #1e1e1e;
                    border: 1px solid #f9f9f8;
                    border-radius: 8px;
                    word-break: break-all;
                ">

                    <strong>LINK DE TESTE:</strong>

                    <br><br>

                    <a href="<?= htmlspecialchars($linkTeste) ?>">
                        <?= htmlspecialchars($linkTeste) ?>
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>

<?php
include __DIR__ . '/../includes/footer.php';
?>
