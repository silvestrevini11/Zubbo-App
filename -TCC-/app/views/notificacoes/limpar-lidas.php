<?php
require_once __DIR__.'/../../middleware/auth.php';
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit('Método não permitido.');}
zubbo_require_csrf();
$id=(int)$_SESSION['usuario']['id'];
try {
    $stmt=$conn->prepare("DELETE FROM Notificacao WHERE id_destinatario=? AND lida=TRUE");
    $stmt->execute([$id]);
    header('Location: notificacoes.php?limpo=1',true,303);
} catch (Throwable $e) {
    error_log('Erro ao limpar notificações: '.get_class($e));
    header('Location: notificacoes.php',true,303);
}
exit;
