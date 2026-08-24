<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: form-usuario.php');
    exit;
}

include __DIR__.'/../../../config/database.php';

$id_user = $_SESSION['usuario']['id'];

$esportes = $_POST['esportes'] ?? [];

foreach ($esportes as $id_esporte) {

    $stmt = $conn->prepare(
        'INSERT INTO Usuario_Esporte (id_user, id_esporte)
         VALUES (?, ?)'
    );

    $stmt->execute([
        $id_user,
        $id_esporte
    ]);
}

header('Location: ../painel/Painel-inicial.php');
exit;

?>