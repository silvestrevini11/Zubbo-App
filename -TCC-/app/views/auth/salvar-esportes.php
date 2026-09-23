<?php

session_start();

if (
    !isset($_SESSION['cadastro_pendente']) ||
    !isset($_SESSION['email_verificado'])
) {
    header('Location: cadastro.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';


/*
|--------------------------------------------------------------------------
| PEGAR DADOS DO CADASTRO
|--------------------------------------------------------------------------
*/

$cadastro = $_SESSION['cadastro_pendente'];


/*
|--------------------------------------------------------------------------
| RECEBER ESPORTES
|--------------------------------------------------------------------------
*/

$esportes = $_POST['esportes'] ?? [];


/*
|--------------------------------------------------------------------------
| VERIFICAR SE ESCOLHEU PELO MENOS UM ESPORTE
|--------------------------------------------------------------------------
*/

if (empty($esportes)) {
    header('Location: escolher-esportes.php?erro=nenhum');
    exit;
}


/*
|--------------------------------------------------------------------------
| LIMPAR IDs DOS ESPORTES
|--------------------------------------------------------------------------
*/

$esportes = array_map('intval', $esportes);

$esportes = array_unique($esportes);


/*
|--------------------------------------------------------------------------
| CRIAR USUÁRIO + ESPORTES
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | COMEÇAR TRANSAÇÃO
    |--------------------------------------------------------------------------
    */

    $conn->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | CRIAR USUÁRIO
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO Usuario
        (
            nome_user,
            email_user,
            tel_user,
            senha_user,
            date_user,
            email_verificado
        )
        VALUES (?, ?, ?, ?, ?, TRUE)
    ");

    $stmt->execute([
        $cadastro['nome'],
        $cadastro['email'],
        $cadastro['telefone'],
        $cadastro['senha'],
        $cadastro['data_nascimento']
    ]);


    /*
    |--------------------------------------------------------------------------
    | PEGAR ID DO USUÁRIO CRIADO
    |--------------------------------------------------------------------------
    */

    $id_user = (int) $conn->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | SALVAR ESPORTES
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO Usuario_Esporte
        (
            id_user,
            id_esporte
        )
        VALUES (?, ?)
    ");


    foreach ($esportes as $id_esporte) {

        $stmt->execute([
            $id_user,
            $id_esporte
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FINALIZAR TRANSAÇÃO
    |--------------------------------------------------------------------------
    */

    $conn->commit();


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | DESFAZER TUDO SE DER ERRO
    |--------------------------------------------------------------------------
    */

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Location: escolher-esportes.php?erro=salvar');
    exit;
}


/*
|--------------------------------------------------------------------------
| LIMPAR SESSÃO DO CADASTRO
|--------------------------------------------------------------------------
*/

unset($_SESSION['cadastro_pendente']);
unset($_SESSION['email_verificado']);


/*
|--------------------------------------------------------------------------
| MENSAGEM DE SUCESSO
|--------------------------------------------------------------------------
*/

$_SESSION['sucesso_login'] =
    'Cadastro concluído! Agora você pode entrar.';


/*
|--------------------------------------------------------------------------
| IR PARA LOGIN
|--------------------------------------------------------------------------
*/

header('Location: login.php');
exit;