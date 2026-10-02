<?php
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: chats.php');
    exit;
}

zubbo_require_csrf();

$idUsuario = (int) $_SESSION['usuario']['id'];
$idConversa = (int) ($_POST['id_conversa'] ?? 0);
$mensagem = trim((string) ($_POST['mensagem'] ?? ''));

if ($idConversa <= 0 || $mensagem === '' || mb_strlen($mensagem) > 2000) {
    header('Location: chats.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT pc_outro.id_user AS id_destinatario
    FROM Conversa c
    INNER JOIN Participantes_Conversa pc
        ON pc.id_conversa = c.id_conversa AND pc.id_user = ?
    INNER JOIN Participantes_Conversa pc_outro
        ON pc_outro.id_conversa = c.id_conversa AND pc_outro.id_user <> ?
    WHERE c.id_conversa = ?
      AND c.tipo_conversa = 'privado'
    LIMIT 1
");
$stmt->execute([$idUsuario, $idUsuario, $idConversa]);
$idDestinatario = $stmt->fetchColumn();

if ($idDestinatario === false) {
    header('Location: chats.php');
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare(
        'INSERT INTO Mensagem (id_conversa, id_remetente, mensagem) VALUES (?, ?, ?)'
    );
    $stmt->execute([$idConversa, $idUsuario, $mensagem]);
    $idMensagem = (int) $conn->lastInsertId();

    $stmt = $conn->prepare(
        "INSERT INTO Notificacao
            (id_destinatario, id_remetente, id_conversa, id_mensagem, tipo)
         VALUES (?, ?, ?, ?, 'mensagem')"
    );
    $stmt->execute([(int) $idDestinatario, $idUsuario, $idConversa, $idMensagem]);

    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Erro ao enviar mensagem: ' . $e->getMessage());
}

header('Location: chats-conversas.php?id=' . (int) $idDestinatario, true, 303);
exit;
