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
   E DESCOBRIR O DESTINATÁRIO
========================================== */

$stmt = $conn->prepare("
    SELECT
        c.id_conversa,
        pc_outro.id_user AS id_destinatario

    FROM Conversa c

    INNER JOIN Participantes_Conversa pc
        ON pc.id_conversa = c.id_conversa
        AND pc.id_user = ?

    INNER JOIN Participantes_Conversa pc_outro
        ON pc_outro.id_conversa = c.id_conversa
        AND pc_outro.id_user <> ?

    WHERE c.id_conversa = ?
      AND c.tipo_conversa = 'privado'
");

$stmt->execute([
    $id_usuario,
    $id_usuario,
    $id_conversa
]);

$conversa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversa) {
    header('Location: chats.php');
    exit;
}

$id_destinatario = (int) $conversa['id_destinatario'];


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