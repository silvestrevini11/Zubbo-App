<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login/login.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';

$id_usuario = (int) $_SESSION['usuario']['id'];
$id_remetente = (int) ($_POST['id_remetente'] ?? 0);

if ($id_remetente <= 0) {
    die('Remetente inválido.');
}

if ($id_usuario === $id_remetente) {
    die('Você não pode aceitar uma solicitação de si mesmo.');
}

/*
 * Procura a solicitação pendente.
 */
$stmt = $conn->prepare("
    SELECT
        id_solicitacao,
        id_remetente,
        id_destinatario,
        status
    FROM Solicitacao_Amizade
    WHERE id_remetente = ?
      AND id_destinatario = ?
      AND status = 'pendente'
    LIMIT 1
");

$stmt->execute([
    $id_remetente,
    $id_usuario
]);

$solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitacao) {
    die('Solicitação de amizade não encontrada ou já foi processada.');
}

$id_solicitacao = (int) $solicitacao['id_solicitacao'];

/*
 * Organiza os IDs para a tabela Amizade.
 */
$id1 = min($id_usuario, $id_remetente);
$id2 = max($id_usuario, $id_remetente);

try {

    $conn->beginTransaction();

    /*
     * 1. Aceita a solicitação
     */
    $stmt = $conn->prepare("
        UPDATE Solicitacao_Amizade
        SET status = 'aceita'
        WHERE id_solicitacao = ?
          AND status = 'pendente'
    ");

    $stmt->execute([
        $id_solicitacao
    ]);

    /*
     * 2. Cria a amizade
     */
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

    /*
     * 3. Cria notificação avisando
     * que a solicitação foi aceita.
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
        VALUES (?, ?, NULL, NULL, 'amizade_aceita')
    ");

    $stmt->execute([
        $id_remetente,
        $id_usuario
    ]);

    /*
     * 4. Confirma
     */
    $conn->commit();

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
