<?php
$stmtNaoLidas = $conn->prepare("
    SELECT n.id_conversa, c.tipo_conversa, COUNT(*) AS total
    FROM Notificacao n
    INNER JOIN Conversa c ON c.id_conversa = n.id_conversa
    INNER JOIN Participantes_Conversa pc
        ON pc.id_conversa = n.id_conversa AND pc.id_user = n.id_destinatario
    WHERE n.id_destinatario = ? AND n.tipo = 'mensagem' AND n.lida = FALSE
      AND c.tipo_conversa IN ('privado', 'grupo')
    GROUP BY n.id_conversa, c.tipo_conversa
");
$stmtNaoLidas->execute([$id_usuario]);

$naoLidas = [];
$tiposComNaoLidas = ['privado' => false, 'grupo' => false];
foreach ($stmtNaoLidas->fetchAll(PDO::FETCH_ASSOC) as $contador) {
    $naoLidas[$contador['id_conversa']] = (int) $contador['total'];
    $tiposComNaoLidas[$contador['tipo_conversa']] = true;
}
