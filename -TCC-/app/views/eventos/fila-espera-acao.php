<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/_regras-equipes.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido.'); }
$idUsuario = (int) $_SESSION['usuario']['id'];
$idEvento = filter_var($_POST['id_evento'] ?? null,FILTER_VALIDATE_INT);
$acao = (string)($_POST['acao'] ?? '');
$origem = ($_POST['origem'] ?? '') === 'detalhes' ? 'detalhes' : 'lista';
$csrf = $_POST['csrf'] ?? '';
if (!$idEvento || !in_array($acao,['entrar','sair'],true) ||
    !is_string($csrf) || empty($_SESSION['csrf_eventos']) ||
    !hash_equals($_SESSION['csrf_eventos'],$csrf)) {
    http_response_code(403); exit('Solicitação inválida.');
}
try {
    $conn->beginTransaction();
    $st = $conn->prepare("SELECT ev.status_evento,
      (TIMESTAMP(ev.data_evento,ev.horario_evento)>=NOW()) aberto,
      e.nome_esporte FROM Evento ev JOIN Esporte e ON ev.id_esporte=e.id_esporte
      WHERE ev.id_evento=? FOR UPDATE");
    $st->execute([$idEvento]);
    $evento=$st->fetch(PDO::FETCH_ASSOC);
    if (!$evento) throw new RuntimeException('Evento indisponível.');
    if ($acao === 'entrar') {
        $limite=evento_vagas_por_time((string)$evento['nome_esporte']);
        if (!$limite || $evento['status_evento']!=='ativo' || !$evento['aberto'])
            throw new RuntimeException('Fila indisponível neste evento.');
        $st=$conn->prepare("SELECT COUNT(*) FROM Solicitacao_Vaga_Evento
          WHERE id_evento=? AND status_solicitacao='aprovada' AND time_num IN (1,2)");
        $st->execute([$idEvento]);
        if ((int)$st->fetchColumn() < $limite*2)
            throw new RuntimeException('Ainda há vaga disponível. Solicite uma vaga na escalação.');
        $st=$conn->prepare("SELECT 1 FROM Lista_Evento WHERE id_evento=? AND id_user=?");
        $st->execute([$idEvento,$idUsuario]);
        if ($st->fetchColumn()) throw new RuntimeException('Você já está no evento.');
        $st=$conn->prepare("SELECT 1 FROM Solicitacao_Vaga_Evento
          WHERE id_evento=? AND id_user=? AND status_solicitacao IN ('pendente','aprovada')");
        $st->execute([$idEvento,$idUsuario]);
        if ($st->fetchColumn()) throw new RuntimeException('Você já possui inscrição ativa.');
        $conn->prepare('INSERT IGNORE INTO Fila_Espera_Evento (id_evento,id_user) VALUES (?,?)')
             ->execute([$idEvento,$idUsuario]);
        $msg='Você entrou na fila de espera.';
    } else {
        $conn->prepare('DELETE FROM Fila_Espera_Evento WHERE id_evento=? AND id_user=?')
             ->execute([$idEvento,$idUsuario]);
        $msg='Você saiu da fila de espera.';
    }
    $conn->commit();
    $_SESSION['flash_vaga_evento'][$idEvento]=['tipo'=>'sucesso','mensagem'=>$msg];
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $_SESSION['flash_vaga_evento'][$idEvento]=['tipo'=>'erro','mensagem'=>$e instanceof RuntimeException?$e->getMessage():'Não foi possível atualizar a fila.'];
}
$pagina=$origem==='detalhes'?'detalhes-evento.php':'lista-presenca.php';
header('Location: '.$pagina.'?id_evento='.$idEvento,true,303); exit;
