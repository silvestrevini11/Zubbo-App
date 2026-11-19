<?php
require_once __DIR__.'/_auth.php'; require_once __DIR__.'/../../../config/database.php';
$status=$_GET['status']??''; $ok=['pendente','aprovado','rejeitado'];
$sql="SELECT l.*, u.nome_user AS criador FROM LocalEsp l LEFT JOIN Usuario u ON u.id_user=l.id_criador"; $p=[]; if(in_array($status,$ok,true)){ $sql.=' WHERE l.status_local=?'; $p[]=$status;} $sql.=' ORDER BY l.id_local DESC LIMIT 200'; $st=$conn->prepare($sql); $st->execute($p); $locais=$st->fetchAll(PDO::FETCH_ASSOC);
$esportesLocais=$conn->query("SELECT id_esporte,nome_esporte FROM Esporte ORDER BY nome_esporte")->fetchAll(PDO::FETCH_ASSOC);
$relacoes=$conn->query("SELECT id_local,id_esporte FROM Local_Esporte_Permitido")->fetchAll(PDO::FETCH_ASSOC);
$modalidadesPorLocal=[];
foreach($relacoes as $rel) $modalidadesPorLocal[(int)$rel['id_local']][(int)$rel['id_esporte']]=true;

$paginaAdmin='locais'; $tituloAdmin='Gerenciar locais'; require __DIR__.'/includes/header.php';
?>
<section class="admin-local-cadastro">
<h2>Cadastrar quadra ou local esportivo</h2>
<form action="criar-local.php" method="post" enctype="multipart/form-data" class="admin-form-card admin-form-organizado">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<label>Nome <input name="nome_local" maxlength="60" required></label>
<label>Endereço completo <input name="endereco_local" maxlength="120" required></label>
<label>Tipo <select name="tipo_local"><option value="quadra">Quadra</option><option value="poliesportivo">Poliesportivo</option><option value="clube">Clube</option><option value="ginásio">Ginásio</option><option value="campo">Campo</option><option value="parque">Parque</option></select></label>
<label>Foto (opcional, até 4 MB) <input type="file" name="foto_local" accept="image/png,image/jpeg,image/webp"></label>
<button type="submit" class="admin-campo-inteiro admin-form-salvar">Cadastrar local</button>
</form>
</section>
<form class="admin-filtros" method="get"><select name="status"><option value="">Todos os status</option><?php foreach($ok as $op): ?><option value="<?= $op ?>" <?= $status===$op?'selected':'' ?>><?= ucfirst($op) ?></option><?php endforeach; ?></select><button>Filtrar</button><a href="locais.php">Limpar</a></form>
<div class="admin-cards-lista"><?php if(!$locais): ?><p class="admin-vazio">Nenhum local encontrado.</p><?php endif; ?><?php foreach($locais as $l): ?><article class="admin-local-card"><div class="admin-local-info"><div><p><?= htmlspecialchars(strtoupper($l['tipo_local'])) ?></p><h2><?= htmlspecialchars($l['nome_local']) ?></h2><span><?= htmlspecialchars($l['endereco_local']) ?></span><small>Criado por <?= htmlspecialchars($l['criador']??'sistema') ?> · #<?= (int)$l['id_local'] ?></small></div><span class="admin-status status-<?= htmlspecialchars($l['status_local']) ?>"><?= htmlspecialchars($l['status_local']) ?></span></div><form class="admin-form-card" action="local-acao.php" method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_local" value="<?= (int)$l['id_local'] ?>"><select name="acao"><option value="aprovado">Aprovar</option><option value="rejeitado">Rejeitar</option><option value="pendente">Voltar para pendente</option></select><input name="motivo" maxlength="255" placeholder="Motivo da decisão" required><button>Salvar decisão</button></form><form action="foto-local.php" method="post" enctype="multipart/form-data" class="admin-form-card admin-form-organizado admin-form-foto">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="id_local" value="<?= (int)$l['id_local'] ?>">
<?php if (!empty($l['foto_local'])): ?><img src="<?= htmlspecialchars(zubbo_url('/'.ltrim((string)$l['foto_local'],'/')), ENT_QUOTES, 'UTF-8') ?>" alt="Foto do local" class="admin-local-foto"><?php endif; ?>
<label>Foto do local <input type="file" name="foto_local" accept="image/png,image/jpeg,image/webp" required></label>
<button type="submit">Atualizar foto</button>
</form>
<form action="modalidades-local.php" method="post" class="admin-form-card admin-form-organizado admin-form-modalidades">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token'],ENT_QUOTES,'UTF-8') ?>">
  <input type="hidden" name="id_local" value="<?= (int)$l['id_local'] ?>">
  <strong class="admin-campo-inteiro">Modalidades confirmadas para este local</strong>
  <p class="admin-form-ajuda admin-campo-inteiro">Marque apenas esportes que a instalação realmente comporta. Não confirma reserva ou autorização de uso.</p>
  <div class="admin-modalidades-lista admin-campo-inteiro">
  <?php foreach($esportesLocais as $esp): ?>
    <label class="admin-opcao"><input type="checkbox" name="esportes[]" value="<?= (int)$esp['id_esporte'] ?>"
     <?= !empty($modalidadesPorLocal[(int)$l['id_local']][(int)$esp['id_esporte']])?'checked':'' ?>>
      <?= htmlspecialchars($esp['nome_esporte'],ENT_QUOTES,'UTF-8') ?></label>
  <?php endforeach; ?>
  </div>
  <label class="admin-campo-inteiro">Fonte da informação / confirmação
   <input type="text" name="fonte" maxlength="500" minlength="8" placeholder="Site, contato ou registro da verificação" required>
  </label>
  <button type="submit" class="admin-campo-inteiro admin-form-salvar">Salvar modalidades</button>
</form></article><?php endforeach; ?></div>
<?php require __DIR__.'/includes/footer.php'; ?>
