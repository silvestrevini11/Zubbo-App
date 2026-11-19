<?php
require_once __DIR__.'/_auth.php'; require_once __DIR__.'/../../../config/database.php';
$status=$_GET['status']??''; $ok=['pendente','aprovada','rejeitada'];
$sql="SELECT s.id_sugestao,s.nome_esporte,s.status_sugestao,s.data_sugestao,s.data_analise,u.nome_user,(SELECT COUNT(*) FROM Voto_Sugestao v WHERE v.id_sugestao=s.id_sugestao) votos FROM Sugestao_Esporte s INNER JOIN Usuario u ON u.id_user=s.id_user"; $p=[]; if(in_array($status,$ok,true)){ $sql.=' WHERE s.status_sugestao=?'; $p[]=$status;} $sql.=' ORDER BY (s.status_sugestao="pendente") DESC, votos DESC, s.data_sugestao DESC LIMIT 200'; $st=$conn->prepare($sql); $st->execute($p); $sug=$st->fetchAll(PDO::FETCH_ASSOC);
$locaisConfirmacao=$conn->query("SELECT id_local,nome_local FROM LocalEsp WHERE status_local='aprovado' ORDER BY nome_local")->fetchAll(PDO::FETCH_ASSOC);
$paginaAdmin='sugestoes'; $tituloAdmin='Sugestões de esportes'; require __DIR__.'/includes/header.php';
?>
<form class="admin-filtros" method="get"><select name="status"><option value="">Todos os status</option><?php foreach($ok as $op): ?><option value="<?= $op ?>" <?= $status===$op?'selected':'' ?>><?= ucfirst($op) ?></option><?php endforeach; ?></select><button>Filtrar</button><a href="sugestoes.php">Limpar</a></form>
<div class="admin-sugestoes-grid"><?php if(!$sug): ?><p class="admin-vazio">Nenhuma sugestão encontrada.</p><?php endif; ?><?php foreach($sug as $s): ?><article class="admin-sugestao-card"><div class="admin-sugestao-top"><span class="admin-sugestao-votos"><b><?= (int)$s['votos'] ?></b> votos</span><span class="admin-status status-<?= htmlspecialchars($s['status_sugestao']) ?>"><?= htmlspecialchars($s['status_sugestao']) ?></span></div><p>SUGESTÃO #<?= (int)$s['id_sugestao'] ?></p><h2><?= htmlspecialchars($s['nome_esporte']) ?></h2><small>Enviada por <?= htmlspecialchars($s['nome_user']) ?> em <?= date('d/m/Y',strtotime($s['data_sugestao'])) ?></small><form class="admin-form-card admin-form-organizado" action="sugestao-acao.php" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_sugestao" value="<?= (int)$s['id_sugestao'] ?>"><select name="acao" class="admin-campo-inteiro" aria-label="Decisão sobre a sugestão"><option value="aprovada">Aprovar e adicionar esporte</option><option value="rejeitada">Rejeitar</option><option value="pendente">Voltar para pendente</option></select><label class="admin-campo-inteiro">Foto do esporte (JPG, PNG ou WEBP, 4 MB) <input type="file" name="foto_esporte" accept="image/png,image/jpeg,image/webp"></label>
<label class="admin-campo-inteiro">Regras da modalidade <textarea name="regras_esporte" maxlength="2000" rows="3" placeholder="Regras para organizar partidas"></textarea></label>
<label>Jogadores por time <input type="number" name="jogadores_por_time" min="1" max="30" placeholder="Ex.: 5"><small class="admin-form-ajuda">Deixe em branco para esportes individuais.</small></label>
<label>Quantidade de times <select name="quantidade_times"><option value="2">2 times</option><option value="1">1 equipe</option></select></label>
<label class="admin-opcao admin-campo-inteiro"><input type="checkbox" name="exige_confirmacao_local" value="1" checked> Exigir confirmação do poliesportivo antes de usar esta modalidade</label>
<label class="admin-campo-inteiro">Locais com aceitação confirmada pelo administrador
<select name="locais_confirmados[]" multiple size="5">
<?php foreach($locaisConfirmacao as $lc): ?>
<option value="<?= (int)$lc['id_local'] ?>"><?= htmlspecialchars($lc['nome_local'],ENT_QUOTES,'UTF-8') ?></option>
<?php endforeach; ?>
</select></label>
<label class="admin-campo-inteiro">Referência do contato/autorização do local
<input name="referencia_local" maxlength="150" placeholder="Data e responsável pelo contato"></label>
<p class="admin-form-ajuda admin-campo-inteiro">Selecione apenas locais cuja aceitação foi verificada. Essa opção não realiza contato automático com o poliesportivo.</p>
<label class="admin-campo-inteiro">Motivo da decisão<input name="motivo" maxlength="255" placeholder="Motivo da decisão" required></label><button class="admin-campo-inteiro admin-form-salvar" type="submit">Salvar decisão</button></form></article><?php endforeach; ?></div>
<?php require __DIR__.'/includes/footer.php'; ?>
