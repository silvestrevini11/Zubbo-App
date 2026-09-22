<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login/login.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];

$id_solicitacao = (int) ($_POST['id_solicitacao'] ?? 0);
$id_notificacao = (int) ($_POST['id_notificacao'] ?? 0);


if ($id_solicitacao <= 0) {
    header('Location: notificacoes.php');
    exit;
}


/* ==========================================
   BUSCAR SOLICITAÇÃO
========================================== */

$stmt = $conn->prepare("
    SELECT
        id_solicitacao,
        id_remetente,
        id_destinatario,
        status
    FROM Solicitacao_Amizade
    WHERE id_solicitacao = ?
      AND id_destinatario = ?
");

$stmt->execute([
    $id_solicitacao,
    $id_usuario
]);

$solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$solicitacao) {
    header('Location: notificacoes.php');
    exit;
}


/* ==========================================
   VERIFICAR STATUS
========================================== */

if ($solicitacao['status'] !== 'pendente') {
    header('Location: notificacoes.php');
    exit;
}


$id_remetente = (int) $solicitacao['id_remetente'];


if ($id_usuario === $id_remetente) {
    header('Location: notificacoes.php');
    exit;
}


/* ==========================================
   ORGANIZAR IDS DA AMIZADE
========================================== */

$id1 = min($id_usuario, $id_remetente);
$id2 = max($id_usuario, $id_remetente);


try {

    $conn->beginTransaction();


    /* ==========================================
       1. ACEITAR SOLICITAÇÃO
    ========================================== */

    $stmt = $conn->prepare("
        UPDATE Solicitacao_Amizade
        SET status = 'aceita'
        WHERE id_solicitacao = ?
          AND status = 'pendente'
    ");

    $stmt->execute([
        $id_solicitacao
    ]);


    /* ==========================================
       2. CRIAR AMIZADE
    ========================================== */

    $stmt = $conn->prepare("
        INSERT INTO Amizade
        (
            id_user_1,
            id_user_2
        )
        VALUES (?, ?)
    ");

    $stmt->execute([
        $id1,
        $id2
    ]);


    /* ==========================================
       3. MARCAR NOTIFICAÇÃO ORIGINAL COMO LIDA
    ========================================== */

    if ($id_notificacao > 0) {

        $stmt = $conn->prepare("
            UPDATE Notificacao
            SET lida = TRUE
            WHERE id_notificacao = ?
              AND id_destinatario = ?
              AND id_remetente = ?
              AND tipo = 'amizade'
        ");

        $stmt->execute([
            $id_notificacao,
            $id_usuario,
            $id_remetente
        ]);
    }


    /* ==========================================
       4. CRIAR NOTIFICAÇÃO DE ACEITAÇÃO
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
        VALUES (?, ?, NULL, NULL, 'amizade_aceita')
    ");

    $stmt->execute([
        $id_remetente,
        $id_usuario
    ]);


    /* ==========================================
       5. CONFIRMAR TUDO
    ========================================== */

    $conn->commit();


    /*
     * Volta para a página de notificações.
     *
     * Isso faz a página ser carregada novamente,
     * removendo o fundo de "não lida" e o botão Aceitar.
     */
    header('Location: notificacoes.php');
    exit;


} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    die(
        '<h2>Erro ao aceitar amizade</h2>' .
        '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
    );
}