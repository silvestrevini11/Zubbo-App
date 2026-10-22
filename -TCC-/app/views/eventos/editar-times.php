<?php
require_once __DIR__ . '/../../middleware/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido.'); }
$idUsuario = (int) $_SESSION['usuario']['id'];
$idEvento = filter_var($_POST['id_evento'] ?? null, FILTER_VALIDATE_INT);
$csrf = $_POST['csrf'] ?? '';
if (!$idEvento || !is_string($csrf) || empty($_SESSION['csrf_eventos']) ||
    !hash_equals($_SESSION['csrf_eventos'],$csrf)) { http_response_code(403); exit('Solicitação inválida.'); }
$time1=trim((string)($_POST['nome_time_1'] ?? ''));
$time2=trim((string)($_POST['nome_time_2'] ?? ''));
$descricao=trim((string)($_POST['descricao_times'] ?? ''));
$mensagem = '';
if ($time1==='' || $time2==='' || mb_strlen($time1)>30 || mb_strlen($time2)>30 || mb_strlen($descricao)>180) {
    $mensagem='Informe nomes de até 30 caracteres e uma descrição de até 180.';
} else {
    try {
        $stmt=$conn->prepare("UPDATE Evento SET nome_time_1=?, nome_time_2=?, descricao_times=?
            WHERE id_evento=? AND id_criador=? AND status_evento<>'removido'");
        $stmt->execute([$time1,$time2,$descricao ?: null,$idEvento,$idUsuario]);
        $mensagem=$stmt->rowCount() ? 'Times atualizados.' : 'Nenhuma alteração ou permissão insuficiente.';
    } catch (Throwable $e) {
        error_log('Falha ao editar times: '.$e->getMessage());
        $mensagem='Não foi possível salvar os times.';
    }
}
$_SESSION['flash_vaga_evento'][$idEvento]=[
  'tipo'=>$mensagem==='Times atualizados.'?'sucesso':'erro','mensagem'=>$mensagem
];
header('Location: detalhes-evento.php?id_evento='.$idEvento,true,303);exit;
