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
| GERAR CÓDIGO
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
| GUARDAR CADASTRO TEMPORARIAMENTE NA SESSÃO
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| O usuário ainda NÃO foi criado no banco.
|
*/

$_SESSION['cadastro_pendente'] = [
    'nome' => $nome,
    'email' => $email,
    'telefone' => $telefone,
    'senha' => password_hash($senha, PASSWORD_DEFAULT),
    'data_nascimento' => $dataNascimento,
    'codigo' => $codigo,
    'expiracao' => $expiracao
];


/*
|--------------------------------------------------------------------------
| CONFIGURAR PHPMailer
|--------------------------------------------------------------------------
*/

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();

    $mail->Host = 'smtp.gmail.com';

    $mail->SMTPAuth = true;

    $mail->Username = 'zubbosupport@gmail.com';

    $mail->Password = 'xqyp cdic ldtg asyc';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;

    $mail->CharSet = 'UTF-8';


    /*
    |--------------------------------------------------------------------------
    | REMETENTE
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        'zubbosupport@gmail.com',
        'Zubbo'
    );


    /*
    |--------------------------------------------------------------------------
    | DESTINATÁRIO
    |--------------------------------------------------------------------------
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
    |--------------------------------------------------------------------------
    | SE O E-MAIL FALHAR, CANCELAR CADASTRO TEMPORÁRIO
    |--------------------------------------------------------------------------
    */

    unset($_SESSION['cadastro_pendente']);

    die(
        'Erro ao enviar o e-mail: ' .
        htmlspecialchars($mail->ErrorInfo)
    );
}


/*
|--------------------------------------------------------------------------
| IR PARA VERIFICAÇÃO
|--------------------------------------------------------------------------
*/

header('Location: verificar-email.php');
exit;