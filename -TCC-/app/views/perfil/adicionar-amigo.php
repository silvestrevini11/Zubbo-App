<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login/login.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_remetente = (int) $_SESSION['usuario']['id'];
$id_destinatario = (int) ($_POST['id_amigo'] ?? 0);

// Verifica se o ID é válido
if ($id_destinatario <= 0) {
    header('Location: ../pesquisa/pesquisa.php');
    exit;
}

// Não pode adicionar a si mesmo
if ($id_remetente === $id_destinatario) {
    header('Location: perfil.php?id=' . $id_destinatario);
    exit;
}

/*
 * Verifica se já são amigos
 */
$id1 = min($id_remetente, $id_destinatario);
$id2 = max($id_remetente, $id_destinatario);

$stmt = $conn->prepare("
    SELECT id_amizade
    FROM Amizade
    WHERE id_user_1 = ?
      AND id_user_2 = ?
");
$stmt->execute([$id1, $id2]);

if ($stmt->fetch()) {
    header('Location: perfil.php?id=' . $id_destinatario);
    exit;
}

/*
 * Verifica se já existe uma solicitação pendente
 * em qualquer direção.
 */
$stmt = $conn->prepare("
    SELECT id_solicitacao
    FROM Solicitacao_Amizade
    WHERE status = 'pendente'
      AND (
          (id_remetente = ? AND id_destinatario = ?)
          OR
          (id_remetente = ? AND id_destinatario = ?)
      )
");
$stmt->execute([
    $id_remetente,
    $id_destinatario,
    $id_destinatario,
    $id_remetente
]);

if ($stmt->fetch()) {
    header('Location: perfil.php?id=' . $id_destinatario);
    exit;
}

/*
 * Cria a solicitação
 */
$stmt = $conn->prepare("
    INSERT INTO Solicitacao_Amizade
    (id_remetente, id_destinatario)
    VALUES (?, ?)
");

$stmt->execute([
    $id_remetente,
    $id_destinatario
]);

/*
 * Cria a notificação
 */
$stmt = $conn->prepare("
    INSERT INTO Notificacao
    (
        id_destinatario,
        id_remetente,
        id_conversa,
        id_mensagem,
        tipo
    )
    VALUES (?, ?, NULL, NULL, 'amizade')
");

$stmt->execute([
    $id_destinatario,
    $id_remetente
]);

header('Location: perfil.php?id=' . $id_destinatario);
exit;