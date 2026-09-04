<?php

session_start();

require_once __DIR__ . '/../../../config/database.php';


/* =========================================
   VERIFICAR SE EXISTE USUÁRIO PARA VALIDAR
========================================= */

if (!isset($_SESSION['usuario_verificacao'])) {
    header('Location: cadastro.php');
    exit;
}

$idUsuario = (int) $_SESSION['usuario_verificacao'];


/* =========================================
   VERIFICAR SE O FORMULÁRIO FOI ENVIADO
========================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verificar-email.php');
    exit;
}


/* =========================================
   PEGAR CÓDIGO
========================================= */

$codigo = trim($_POST['codigo'] ?? '');


/* =========================================
   VALIDAR FORMATO
========================================= */

if (!preg_match('/^[0-9]{6}$/', $codigo)) {

    $_SESSION['erro_verificacao'] =
        'Digite um código válido de 6 números.';

    header('Location: verificar-email.php');
    exit;
}


/* =========================================
   PROCURAR CÓDIGO NO BANCO
========================================= */

$stmt = $conn->prepare("
    SELECT
        id_verificacao,
        codigo,
        expiracao

    FROM Verificacao_Email

    WHERE id_user = :id_user
      AND codigo = :codigo

    ORDER BY id_verificacao DESC

    LIMIT 1
");

$stmt->execute([
    ':id_user' => $idUsuario,
    ':codigo' => $codigo
]);

$verificacao = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================
   CÓDIGO NÃO ENCONTRADO
========================================= */

if (!$verificacao) {

    $_SESSION['erro_verificacao'] =
        'Código de verificação incorreto.';

    header('Location: verificar-email.php');
    exit;
}


/* =========================================
   VERIFICAR EXPIRAÇÃO
========================================= */

if (strtotime($verificacao['expiracao']) < time()) {

    $_SESSION['erro_verificacao'] =
        'Esse código expirou. Solicite um novo código.';

    header('Location: verificar-email.php');
    exit;
}


/* =========================================
   CONFIRMAR EMAIL
========================================= */

$stmt = $conn->prepare("
    UPDATE Usuario

    SET email_verificado = TRUE

    WHERE id_user = :id_user
");

$stmt->execute([
    ':id_user' => $idUsuario
]);


/* =========================================
   APAGAR CÓDIGO UTILIZADO
========================================= */

$stmt = $conn->prepare("
    DELETE FROM Verificacao_Email

    WHERE id_user = :id_user
");

$stmt->execute([
    ':id_user' => $idUsuario
]);


/* =========================================
   FINALIZAR VERIFICAÇÃO
========================================= */

unset($_SESSION['usuario_verificacao']);

$_SESSION['usuario_cadastro'] = $idUsuario;

unset($_SESSION['usuario_verificacao']);

header('Location: escolher-esportes.php');
exit;