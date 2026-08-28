<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../usuario/form-usuario.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];

$id_conversa = (int) ($_POST['id_conversa'] ?? 0);

$mensagem = trim($_POST['mensagem'] ?? '');

if ($id_conversa <= 0 || $mensagem === '') {
    header('Location: chats.php');
    exit;
}


/*
    Verifica se o usuário realmente pertence
    a essa conversa.
*/

$stmt = $conn->prepare("
    SELECT id_conversa
    FROM Conversa
    WHERE id_conversa = ?
      AND (id_user_a = ? OR id_user_b = ?)
");

$stmt->execute([
    $id_conversa,
    $id_usuario,
    $id_usuario
]);

$conversa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversa) {
    header('Location: chats.php');
    exit;
}


/* Salvar mensagem */

$stmt = $conn->prepare("
    INSERT INTO Mensagem
        (id_conversa, id_remetente, mensagem)
    VALUES
        (?, ?, ?)
");

$stmt->execute([
    $id_conversa,
    $id_usuario,
    $mensagem
]);


/*
    Descobrir com quem estamos conversando
    para voltar para a conversa.
*/

$stmt = $conn->prepare("
    SELECT
        CASE
            WHEN id_user_a = ? THEN id_user_b
            ELSE id_user_a
        END AS outro_usuario
    FROM Conversa
    WHERE id_conversa = ?
");

$stmt->execute([
    $id_usuario,
    $id_conversa
]);

$dados = $stmt->fetch(PDO::FETCH_ASSOC);

$id_outro_usuario = $dados['outro_usuario'];


/* Volta para a conversa */

header(
    'Location: chats-conversas.php?id=' . $id_outro_usuario
);

exit;