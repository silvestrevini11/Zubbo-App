<?php
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: chats-grupos.php');
    exit;
}

zubbo_require_csrf();

$idUsuario = (int) $_SESSION['usuario']['id'];
$idConversa = filter_var($_POST['id_conversa'] ?? null, FILTER_VALIDATE_INT);
$mensagem = trim((string) ($_POST['mensagem'] ?? ''));

if (!$idConversa || $idConversa < 1 || $mensagem === '' || mb_strlen($mensagem) > 2000) {
    header('Location: chats-grupos.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT 1
    FROM Grupo g
    INNER JOIN Conversa c ON c.id_conversa = g.id_conversa AND c.tipo_conversa = 'grupo'
    INNER JOIN Participantes_Conversa pc ON pc.id_conversa = g.id_conversa
    WHERE g.id_conversa = ? AND pc.id_user = ?
    LIMIT 1
");
$stmt->execute([$idConversa, $idUsuario]);

if (!$stmt->fetchColumn()) {
    http_response_code(403);
    exit('Você não participa desta conversa.');
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare(
        'INSERT INTO Mensagem (id_conversa, id_remetente, mensagem) VALUES (?, ?, ?)'
    );
    $stmt->execute([$idConversa, $idUsuario, $mensagem]);
    $idMensagem = (int) $conn->lastInsertId();

    $stmtNotificacao = $conn->prepare("
        INSERT INTO Notificacao (id_destinatario, id_remetente, id_conversa, id_mensagem, tipo)
        SELECT pc.id_user, ?, ?, ?, 'mensagem'
        FROM Participantes_Conversa pc
        WHERE pc.id_conversa = ? AND pc.id_user <> ?
    ");
    $stmtNotificacao->execute([$idUsuario, $idConversa, $idMensagem, $idConversa, $idUsuario]);
    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Erro ao enviar mensagem de grupo: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível enviar a mensagem.');
}

header('Location: chat-grupo.php?id_conversa=' . $idConversa, true, 303);
exit;
