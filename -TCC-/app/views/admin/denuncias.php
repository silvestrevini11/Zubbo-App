<?php
require_once __DIR__.'/_auth.php'; require_once __DIR__.'/../../../config/database.php';
$status=$_GET['status']??''; $ok=['pendente','em_analise','resolvida','rejeitada'];
$sql="
SELECT d.*, denunciante.nome_user AS denunciante_nome, denunciado.nome_user AS denunciado_nome,
       ev.nome_evento, c.tipo_conversa
FROM Denuncia d
INNER JOIN Usuario denunciante ON denunciante.id_user=d.id_denunciante
LEFT JOIN Usuario denunciado ON denunciado.id_user=d.id_denunciado
LEFT JOIN Evento ev ON ev.id_evento=d.id_evento
LEFT JOIN Conversa c ON c.id_conversa=d.id_conversa
"; $p=[]; if(in_array($status,$ok,true)){ $sql.=' WHERE d.status_denuncia=?'; $p[]=$status;} $sql.=' ORDER BY d.data_denuncia DESC LIMIT 200'; $st=$conn->prepare($sql); $st->execute($p); $denuncias=$st->fetchAll(PDO::FETCH_ASSOC);
$paginaAdmin='denuncias'; $tituloAdmin='Central de denúncias'; require __DIR__.'/includes/header.php';
?>
<form class="admin-filtros" method="get"><select name="status"><option value="">Todos os status</option><?php foreach($ok as $op): ?><option value="<?= $op ?>" <?= $status===$op?'selected':'' ?>><?= str_replace('_',' ',ucfirst($op)) ?></option><?php endforeach; ?></select><button>Filtrar</button><a href="denuncias.php">Limpar</a></form>
<div class="admin-cards-lista"><?php if(!$denuncias): ?><p class="admin-vazio">Nenhuma denúncia encontrada.</p><?php endif; ?><?php foreach($denuncias as $d): ?>
<article class="admin-denuncia-card"><header><div><p>DENÚNCIA #<?= (int)$d['id_denuncia'] ?> · <?= date('d/m/Y H:i',strtotime($d['data_denuncia'])) ?></p><h2><?= htmlspecialchars($d['motivo']) ?></h2></div><span class="admin-status status-<?= htmlspecialchars($d['status_denuncia']) ?>"><?= htmlspecialchars(str_replace('_',' ',$d['status_denuncia'])) ?></span></header><div class="admin-denuncia-meta"><span><b>Denunciante:</b> <?= htmlspecialchars($d['denunciante_nome']) ?></span><?php if($d['denunciado_nome']): ?><span><b>Usuário denunciado:</b> <?= htmlspecialchars($d['denunciado_nome']) ?></span><?php endif; ?><?php if($d['nome_evento']): ?><span><b>Evento:</b> <?= htmlspecialchars($d['nome_evento']) ?></span><?php endif; ?><?php if($d['id_mensagem']): ?><span><b>Mensagem:</b> #<?= (int)$d['id_mensagem'] ?></span><?php endif; ?><?php if($d['tipo_conversa']): ?><span><b>Conversa:</b> <?= htmlspecialchars($d['tipo_conversa']) ?></span><?php endif; ?></div><?php if($d['descricao']): ?><p class="admin-denuncia-desc"><?= nl2br(htmlspecialchars($d['descricao'])) ?></p><?php endif; ?><form class="admin-form-card" action="denuncia-acao.php" method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_denuncia" value="<?= (int)$d['id_denuncia'] ?>"><select name="acao"><option value="em_analise">Colocar em análise</option><option value="resolvida">Marcar como resolvida</option><option value="rejeitada">Rejeitar denúncia</option><option value="pendente">Voltar para pendente</option></select><input name="motivo" maxlength="255" placeholder="Observação da moderação" required><button>Atualizar denúncia</button></form></article>
<?php endforeach; ?></div>
<?php require __DIR__.'/includes/footer.php'; ?>
