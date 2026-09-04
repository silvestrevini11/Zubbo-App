<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| RECEBER DADOS
|--------------------------------------------------------------------------
*/

$nome = trim($_POST['name-txt'] ?? '');
$email = trim($_POST['email-txt'] ?? '');
$telefone = preg_replace('/\D/', '', $_POST['telefone-tel'] ?? '');

$senha = $_POST['Senha-pass'] ?? '';
$confirmarSenha = $_POST['confirmar-senha'] ?? '';

$dataNascimento = $_POST['data-nasc'] ?? '';


/*
|--------------------------------------------------------------------------
| VALIDAR DADOS
|--------------------------------------------------------------------------
*/

if (
    $nome === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($telefone) !== 11 ||
    $senha === '' ||
    $senha !== $confirmarSenha ||
    $dataNascimento === ''
) {
    header('Location: cadastro.php?erro=dados');
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR SE E-MAIL JÁ EXISTE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id_user
    FROM Usuario
    WHERE email_user = ?
    LIMIT 1
");

$stmt->execute([$email]);

if ($stmt->fetch()) {
    header('Location: cadastro.php?erro=email');
    exit;
}


/*
|--------------------------------------------------------------------------
| CRIAR USUÁRIO
|--------------------------------------------------------------------------
*/

try {

    $stmt = $conn->prepare("
        INSERT INTO Usuario
        (
            nome_user,
            email_user,
            tel_user,
            senha_user,
            date_user
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nome,
        $email,
        $telefone,
        password_hash($senha, PASSWORD_DEFAULT),
        $dataNascimento
    ]);

    $id_user = (int) $conn->lastInsertId();

} catch (PDOException $e) {

    header('Location: cadastro.php?erro=email');
    exit;
}


/*
|--------------------------------------------------------------------------
| GERAR CÓDIGO DE 6 DÍGITOS
|--------------------------------------------------------------------------
*/

$codigo = str_pad(
    random_int(0, 999999),
    6,
    '0',
    STR_PAD_LEFT
);


/*
|--------------------------------------------------------------------------
| CÓDIGO EXPIRA EM 10 MINUTOS
|--------------------------------------------------------------------------
*/

$expiracao = date(
    'Y-m-d H:i:s',
    strtotime('+10 minutes')
);


/*
|--------------------------------------------------------------------------
| SALVAR CÓDIGO NO BANCO
|--------------------------------------------------------------------------
*/

try {

    $stmt = $conn->prepare("
        INSERT INTO Verificacao_Email
        (
            id_user,
            codigo,
            expiracao
        )
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $id_user,
        $codigo,
        $expiracao
    ]);

} catch (PDOException $e) {

    header('Location: cadastro.php?erro=verificacao');
    exit;
}


/*
|--------------------------------------------------------------------------
| CONFIGURAR PHPMailer
|--------------------------------------------------------------------------
*/

$mail = new PHPMailer(true);

try {

    // Usar SMTP
    $mail->isSMTP();

    // Servidor SMTP do Gmail
    $mail->Host = 'smtp.gmail.com';

    // Ativar autenticação
    $mail->SMTPAuth = true;

    // E-MAIL DO ZUBBO
    $mail->Username = 'zubboadms@gmail.com';

    // SENHA DE APLICATIVO DO GOOGLE
    // COLOQUE A SUA AQUI
    $mail->Password = 'gksi zwrg tgxc upnr';

    // Segurança
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    // Porta do Gmail
    $mail->Port = 587;

    // UTF-8
    $mail->CharSet = 'UTF-8';


    /*
    |--------------------------------------------------------------------------
    | REMETENTE
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        'zubboadms@gmail.com',
        'Zubbo'
    );


    /*
    |--------------------------------------------------------------------------
    | DESTINATÁRIO
    |--------------------------------------------------------------------------
    |
    | Aqui entra o e-mail que o usuário colocou no cadastro.
    |
    */

    $mail->addAddress(
        $email,
        $nome
    );


    /*
    |--------------------------------------------------------------------------
    | ASSUNTO
    |--------------------------------------------------------------------------
    */

    $mail->Subject = 'Código de verificação - Zubbo';


    /*
    |--------------------------------------------------------------------------
    | E-MAIL HTML
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(true);

    $mail->Body = "
        <div style='
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: auto;
            padding: 30px;
        '>

            <h2 style='color: #222;'>
                Verifique seu e-mail
            </h2>

            <p>
                Olá, <strong>" .
                htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') .
                "</strong>!
            </p>

            <p>
                Obrigado por criar sua conta no Zubbo.
            </p>

            <p>
                Use o código abaixo para confirmar seu e-mail:
            </p>

            <div style='
                background: #f3f4f6;
                padding: 20px;
                text-align: center;
                margin: 25px 0;
            '>

                <span style='
                    font-size: 36px;
                    font-weight: bold;
                    letter-spacing: 8px;
                    color: #2563eb;
                '>
                    {$codigo}
                </span>

            </div>

            <p>
                Este código é válido por
                <strong>10 minutos</strong>.
            </p>

            <p>
                Se você não criou uma conta no Zubbo,
                ignore este e-mail.
            </p>

            <br>

            <p>
                Atenciosamente,<br>
                <strong>Equipe Zubbo</strong>
            </p>

        </div>
    ";


    /*
    |--------------------------------------------------------------------------
    | VERSÃO TEXTO
    |--------------------------------------------------------------------------
    */

    $mail->AltBody =
        "Olá, {$nome}!\n\n" .
        "Seu código de verificação do Zubbo é: {$codigo}\n\n" .
        "Este código é válido por 10 minutos.\n\n" .
        "Equipe Zubbo";


    /*
    |--------------------------------------------------------------------------
    | ENVIAR
    |--------------------------------------------------------------------------
    */

    $mail->send();

} catch (Exception $e) {

    /*
    | Durante os testes podemos mostrar o erro.
    | Depois podemos esconder essa informação.
    */

    die(
        'Erro ao enviar o e-mail: ' .
        htmlspecialchars($mail->ErrorInfo)
    );
}


/*
|--------------------------------------------------------------------------
| CRIAR SESSÃO
|--------------------------------------------------------------------------
*/

$_SESSION['usuario_verificacao'] = $id_user;


/*
|--------------------------------------------------------------------------
| IR PARA VERIFICAÇÃO
|--------------------------------------------------------------------------
*/

header('Location: verificar-email.php');
exit;