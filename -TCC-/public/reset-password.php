<?php

session_start();

require_once __DIR__.'/../config/database.php';

$erro = '';
$sucesso = '';

$token = trim($_GET['token'] ?? '');

if ($token === '') {

    $erro = 'Link de recuperação inválido.';

} else {

    /*
     * Gera o hash do token recebido
     */
    $tokenHash = hash('sha256', $token);

    /*
     * Procura o token no banco
     */
    $sql = "SELECT
                r.id_recuperacao,
                r.id_user,
                r.expiracao,
                r.usado,
                u.email_user
            FROM Recuperacao_Senha r
            INNER JOIN Usuario u
                ON u.id_user = r.id_user
            WHERE r.token_hash = :token_hash
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':token_hash' => $tokenHash
    ]);

    $recuperacao = $stmt->fetch();

    /*
     * Verifica se o token existe
     */
    if (!$recuperacao) {

        $erro = 'Link de recuperação inválido ou inexistente.';

    }

    /*
     * Verifica se o token já foi usado
     */
    elseif ((int)$recuperacao['usado'] === 1) {

        $erro = 'Este link de recuperação já foi utilizado.';

    }

    /*
     * Verifica se expirou
     */
    elseif (strtotime($recuperacao['expiracao']) < time()) {

        $erro = 'Este link de recuperação expirou. Solicite um novo link.';

    }

}


/*
 * Processamento da nova senha
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $erro === ''
) {

    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    /*
     * Validação da senha
     */
    if (strlen($novaSenha) < 8) {

        $erro = 'A senha deve ter pelo menos 8 caracteres.';

    }

    elseif ($novaSenha !== $confirmarSenha) {

        $erro = 'As senhas não são iguais.';

    }

    else {

        /*
         * Gera o hash seguro da nova senha
         */
        $senhaHash = password_hash(
            $novaSenha,
            PASSWORD_DEFAULT
        );

        /*
         * Atualiza a senha do usuário
         */
        $sqlUpdate = "UPDATE Usuario
                      SET senha_user = :senha
                      WHERE id_user = :id_user";

        $stmtUpdate = $conn->prepare($sqlUpdate);

        $stmtUpdate->execute([
            ':senha' => $senhaHash,
            ':id_user' => $recuperacao['id_user']
        ]);

        /*
         * Marca o token como usado
         */
        $sqlToken = "UPDATE Recuperacao_Senha
                     SET usado = TRUE
                     WHERE id_recuperacao = :id_recuperacao";

        $stmtToken = $conn->prepare($sqlToken);

        $stmtToken->execute([
            ':id_recuperacao' => $recuperacao['id_recuperacao']
        ]);

        $sucesso = 'Sua senha foi alterada com sucesso.';
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

    <title>Redefinir senha</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>

<body class="esqueci-senha-body">

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
                        d="M17,9V7c0-2.8-2.2-5-5-5S7,4.2,7,7v2c-1.7,0-3,1.3-3,3v7c0,1.7,1.3,3,3,3h10c1.7,0,3-1.3,3-3v-7C20,10.3,18.7,9,17,9z M9,7c0-1.7,1.3-3,3-3s3,1.3,3,3v2H9V7z M13.1,15.5c0,0-0.1,0.1-0.1,0.1V17c0,0.6-0.4,1-1,1s-1-0.4-1-1v-1.4c-0.6-0.6-0.7-1.5-0.1-2.1c0.6-0.6-0.7-1.5-0.1-2.1c0.6-0.6,1.5-0.7,2.1-0.1C13.6,13.9,13.7,14.9,13.1,15.5z"
                    >

                    </path>

                </svg>

            </div>

            <h1>Redefinir senha</h1>

            <?php if ($erro): ?>

                <div class="alert erro-esqueci-senha">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>


            <?php if ($sucesso): ?>

                <div class="alert sucesso-esqueci-senha">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

                <a
                    href="../app/views/auth/login.php"
                    class="btn-esqueci-senha"
                    style="
                        display: block;
                        text-align: center;
                        text-decoration: none;
                        margin-top: 20px;
                    "
                >
                    Ir para o login
                </a>

            <?php elseif ($erro === ''): ?>

                <p class="descricao-esqueci-senha">
                    Digite sua nova senha abaixo.
                </p>

                <form method="POST">

                    <label for="nova_senha">
                        Nova senha
                    </label>

                    <input
                        type="password"
                        id="nova_senha"
                        name="nova_senha"
                        placeholder="Digite sua nova senha"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >

                    <label
                        for="confirmar_senha"
                        style="margin-top: 15px;"
                    >
                        Confirmar nova senha
                    </label>

                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        placeholder="Digite novamente sua senha"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >

                    <button
                        class="btn-esqueci-senha"
                        type="submit"
                    >
                        Alterar senha
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>