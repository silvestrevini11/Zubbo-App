<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: form-usuario.php');
    exit;
}

include __DIR__.'/../../../config/database.php';

$id_user = (int) $_SESSION['usuario']['id'];

// Evita salvar esportes para um ID que não existe mais no banco.
$verificaUsuario = $conn->prepare('SELECT 1 FROM Usuario WHERE id_user = ?');
$verificaUsuario->execute([$id_user]);
if (!$verificaUsuario->fetchColumn()) {
    $_SESSION = [];
    session_destroy();
    header('Location: cadastro.php?erro=sessao');
    exit;
}

$esportes = $_POST['esportes'] ?? [];

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare('INSERT INTO Usuario_Esporte (id_user, id_esporte) VALUES (?, ?)');

    foreach ($esportes as $id_esporte) {
        $stmt->execute([$id_user, (int) $id_esporte]);
    }

    $conn->commit();
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header('Location: escolher-esportes.php?erro=esportes');
    exit;
}

header('Location: ../painel/Painel-inicial.php');
exit;

?>
