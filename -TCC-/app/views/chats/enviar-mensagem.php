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


/* ==========================================
   VERIFICAR SE O USUÁRIO PERTENCE À CONVERSA
========================================== */

$stmt = $conn->prepare("
    SELECT
        id_conversa,
        id_user_a,
        id_user_b
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


/* ==========================================
   DESCOBRIR O DESTINATÁRIO
========================================== */

if ((int) $conversa['id_user_a'] === $id_usuario) {

    $id_destinatario = (int) $conversa['id_user_b'];

} else {

    $id_destinatario = (int) $conversa['id_user_a'];

}


/* ==========================================
   SALVAR MENSAGEM
========================================== */

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


/* ==========================================
   PEGAR ID DA MENSAGEM
========================================== */

$id_mensagem = $conn->lastInsertId();


/* ==========================================
   CRIAR NOTIFICAÇÃO
========================================== */

$stmt = $conn->prepare("
    INSERT INTO Notificacao
        (
            id_destinatario,
            id_remetente,
            id_conversa,
            id_mensagem,
            tipo
        )
    VALUES
        (?, ?, ?, ?, 'mensagem')
");

$stmt->execute([
    $id_destinatario,
    $id_usuario,
    $id_conversa,
    $id_mensagem
]);


/* ==========================================
   VOLTAR PARA A CONVERSA
========================================== */

header(
    'Location: chats-conversas.php?id=' . $id_destinatario
);

exit;