<?php

session_start();

if (!isset($_SESSION['usuario_cadastro'])) {
    header('Location: cadastro.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$id_user = (int) $_SESSION['usuario_cadastro'];


/*
|----------------------------------------------------------
| VERIFICAR SE O USUÁRIO EXISTE
|----------------------------------------------------------
*/

$verificaUsuario = $conn->prepare("
    SELECT id_user
    FROM Usuario
    WHERE id_user = ?
");

$verificaUsuario->execute([$id_user]);

if (!$verificaUsuario->fetch()) {

    unset($_SESSION['usuario_cadastro']);

    header('Location: cadastro.php?erro=sessao');
    exit;
}


/*
|----------------------------------------------------------
| RECEBER ESPORTES
|----------------------------------------------------------
*/

$esportes = $_POST['esportes'] ?? [];


/*
|----------------------------------------------------------
| SALVAR ESPORTES
|----------------------------------------------------------
*/

try {

    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO Usuario_Esporte (
            id_user,
            id_esporte
        )
        VALUES (?, ?)
    ");

    foreach ($esportes as $id_esporte) {

        $stmt->execute([
            $id_user,
            (int) $id_esporte
        ]);
    }

    $conn->commit();

} catch (PDOException $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header(
        'Location: escolher-esportes.php?erro=esportes'
    );

    exit;
}


/*
|----------------------------------------------------------
| FINALIZAR CADASTRO
|----------------------------------------------------------
*/

unset($_SESSION['usuario_cadastro']);

$_SESSION['sucesso_login'] =
    'Cadastro concluído! Agora você pode entrar.';

header('Location: login.php');
exit;
?>