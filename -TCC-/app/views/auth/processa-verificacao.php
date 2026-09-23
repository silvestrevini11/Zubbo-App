<?php

session_start();


/*
|--------------------------------------------------------------------------
| VERIFICAR SE EXISTE CADASTRO PENDENTE
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['cadastro_pendente'])) {
    header('Location: cadastro.php');
    exit;
}


$cadastro = $_SESSION['cadastro_pendente'];


/*
|--------------------------------------------------------------------------
| VERIFICAR SE O FORMULÁRIO FOI ENVIADO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verificar-email.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| PEGAR CÓDIGO
|--------------------------------------------------------------------------
*/

$codigo = trim($_POST['codigo'] ?? '');


/*
|--------------------------------------------------------------------------
| VALIDAR FORMATO
|--------------------------------------------------------------------------
*/

if (!preg_match('/^[0-9]{6}$/', $codigo)) {

    $_SESSION['erro_verificacao'] =
        'Digite um código válido de 6 números.';

    header('Location: verificar-email.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR CÓDIGO
|--------------------------------------------------------------------------
*/

if ($codigo !== $cadastro['codigo']) {

    $_SESSION['erro_verificacao'] =
        'Código de verificação incorreto.';

    header('Location: verificar-email.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR EXPIRAÇÃO
|--------------------------------------------------------------------------
*/

if (strtotime($cadastro['expiracao']) < time()) {

    $_SESSION['erro_verificacao'] =
        'Esse código expirou. Solicite um novo código.';

    header('Location: verificar-email.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| E-MAIL VERIFICADO
|--------------------------------------------------------------------------
*/

$_SESSION['email_verificado'] = true;


/*
|--------------------------------------------------------------------------
| IR PARA ESCOLHA DOS ESPORTES
|--------------------------------------------------------------------------
*/

header('Location: escolher-esportes.php');
exit;