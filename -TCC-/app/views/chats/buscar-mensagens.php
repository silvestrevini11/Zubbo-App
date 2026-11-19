<?php
require_once __DIR__ . '/../../middleware/auth.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$idConversa = filter_var($_GET['id_conversa'] ?? null, FILTER_VALIDATE_INT);

if (!$idConversa || $idConversa < 1) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare(
    'SELECT 1
     FROM Participantes_Conversa
     WHERE id_conversa = ? AND id_user = ?
     LIMIT 1'
);
$stmt->execute([$idConversa, $idUsuario]);

if (!$stmt->fetchColumn()) {
    http_response_code(403);
    exit;
}

// Impede ler mensagens de chats privados sem amizade atualmente aceita.
require_once __DIR__ . '/../../services/AmizadeService.php';
$parConversa = $conn->prepare("
    SELECT c.tipo_conversa,
           (SELECT pc2.id_user FROM Participantes_Conversa pc2
            WHERE pc2.id_conversa = c.id_conversa AND pc2.id_user <> ?
            LIMIT 1) AS outro_id
    FROM Conversa c WHERE c.id_conversa = ? LIMIT 1
");
$parConversa->execute([$idUsuario, $idConversa]);
$tipo = $parConversa->fetch(PDO::FETCH_ASSOC);
if (!$tipo || ($tipo['tipo_conversa'] === 'privado'
    && !zubbo_amizade_aceita($conn, $idUsuario, (int)$tipo['outro_id']))) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => 'amizade_necessaria']);
    exit;
}

// Bloqueios também são respeitados na consulta assíncrona das conversas privadas.
$stBloqueio = $conn->prepare("SELECT 1 FROM Conversa c
    INNER JOIN Participantes_Conversa p ON p.id_conversa = c.id_conversa
        AND p.id_user <> ?
    INNER JOIN Bloqueio_Usuario b ON
        (b.id_bloqueador = ? AND b.id_bloqueado = p.id_user) OR
        (b.id_bloqueado = ? AND b.id_bloqueador = p.id_user)
    WHERE c.id_conversa = ? AND c.tipo_conversa = 'privado' LIMIT 1");
$stBloqueio->execute([$idUsuario, $idUsuario, $idUsuario, $idConversa]);
if ($stBloqueio->fetchColumn()) {
    http_response_code(403);
    exit;
}

$stmt = $conn->prepare(
    'SELECT m.id_mensagem, m.id_remetente, m.mensagem, m.data_envio, u.nome_user
     FROM Mensagem m
     INNER JOIN Usuario u ON u.id_user = m.id_remetente
     WHERE m.id_conversa = ?
     ORDER BY m.data_envio ASC, m.id_mensagem ASC'
);
$stmt->execute([$idConversa]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Uma conversa aberta e consultada considera as mensagens recebidas como lidas.
$conn->prepare("UPDATE Notificacao SET lida = TRUE, data_leitura = COALESCE(data_leitura, NOW()) WHERE id_destinatario = ? AND id_conversa = ? AND tipo = 'mensagem' AND lida = FALSE")
    ->execute([$idUsuario, $idConversa]);

foreach ($mensagens as &$msg) {
    $msg['minha'] = (int) $msg['id_remetente'] === $idUsuario;
}
unset($msg);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($mensagens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
