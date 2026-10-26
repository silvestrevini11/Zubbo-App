<?php
require_once __DIR__.'/../../middleware/auth.php';
require_once __DIR__.'/../../services/GrupoService.php';
$idUsuario=(int)$_SESSION['usuario']['id'];
$idGrupo=filter_var($_GET['id_grupo']??null,FILTER_VALIDATE_INT);
if(!$idGrupo){http_response_code(404);exit('Grupo não encontrado.');}
$st=$conn->prepare("SELECT g.id_grupo,g.id_conversa,g.id_criador,g.nome_grupo,g.descricao_grupo,
  (SELECT COUNT(*) FROM Participantes_Conversa pc2
     JOIN Usuario u ON u.id_user=pc2.id_user AND u.status_user='ativo'
   WHERE pc2.id_conversa=g.id_conversa) AS quantidade
 FROM Grupo g JOIN Participantes_Conversa pc ON pc.id_conversa=g.id_conversa
 WHERE g.id_grupo=? AND pc.id_user=? LIMIT 1");
$st->execute([$idGrupo,$idUsuario]);
$grupo=$st->fetch(PDO::FETCH_ASSOC);
if(!$grupo){http_response_code(403);exit('Você não faz parte deste grupo.');}
$moderador=zubbo_grupo_moderador($conn,$grupo,$idUsuario);
$st=$conn->prepare("SELECT u.id_user,u.nome_user,u.foto_user,
      (u.id_user=? OR gm.id_user IS NOT NULL) AS moderador
    FROM Participantes_Conversa pc JOIN Usuario u ON u.id_user=pc.id_user
    LEFT JOIN Grupo_Moderador gm ON gm.id_grupo=? AND gm.id_user=pc.id_user
    WHERE pc.id_conversa=? AND u.status_user='ativo' ORDER BY u.nome_user,u.id_user");
$st->execute([$grupo['id_criador'],$idGrupo,$grupo['id_conversa']]);
$participantes=$st->fetchAll(PDO::FETCH_ASSOC);
$erro=$_SESSION['grupo_moderacao_erro']??'';
$sucesso=$_SESSION['grupo_moderacao_sucesso']??'';
unset($_SESSION['grupo_moderacao_erro'],$_SESSION['grupo_moderacao_sucesso']);
include __DIR__.'/../includes/head.php';
?>
<main class="configuracoes grupo-configuracoes">
  <a class="grupo-config-voltar" href="chat-grupo.php?id_conversa=<?= (int)$grupo['id_conversa'] ?>"><span aria-hidden="true">←</span> Voltar ao grupo</a>
  <?php if($erro): ?><p class="grupo-criar-alerta" role="alert"><?= htmlspecialchars($erro,ENT_QUOTES,'UTF-8') ?></p><?php endif; ?>
  <?php if($sucesso): ?><p class="grupo-lista-sucesso" role="status"><?= htmlspecialchars($sucesso,ENT_QUOTES,'UTF-8') ?></p><?php endif; ?>
  <header class="grupo-config-cabecalho">
    <p class="grupo-config-legenda">SEU GRUPO</p>
    <h1>Configurações do grupo</h1>
    <div class="grupo-config-resumo">
      <span class="grupo-config-avatar" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M21 20v-2a6 6 0 0 0-4-5.65"/></svg>
      </span>
      <div>
        <h2><?= htmlspecialchars($grupo['nome_grupo'],ENT_QUOTES,'UTF-8') ?></h2>
        <p><?= (int)$grupo['quantidade'] ?> <?= (int)$grupo['quantidade'] === 1 ? 'participante ativo' : 'participantes ativos' ?></p>
      </div>
    </div>
  </header>
  <?php if($moderador): ?>
  <section class="configuracoes-grupo grupo-config-card">
    <h3>Organizar encontro esportivo</h3>
    <p>Crie um evento e convide todos os participantes do grupo. Cada pessoa poderá confirmar a própria participação no evento.</p>
    <a class="grupo-config-acao grupo-config-acao-principal"
       href="../eventos/criar-evento.php?id_grupo=<?= (int)$grupo['id_grupo'] ?>"><span>Criar evento e convidar o grupo</span><span aria-hidden="true">→</span></a>
  </section>
  <section class="configuracoes-grupo grupo-config-card">
    <h3>Administração do grupo</h3>
    <p>Edite os dados do grupo e escolha integrantes para ajudar na moderação.</p>
    <a class="grupo-config-acao"
       href="editar-grupo.php?id_grupo=<?= (int)$grupo['id_grupo'] ?>"><span>Editar nome, foto e participantes</span><span aria-hidden="true">→</span></a>
  </section>
  <?php endif; ?>
  <section class="configuracoes-grupo grupo-config-card">
    <h3>Integrantes do grupo</h3>
    <p>Veja quem participa do grupo. Toque em um integrante para abrir o perfil.</p>
    <ul class="grupo-moderadores-lista">
      <?php foreach($participantes as $p): ?>
      <li>
        <?php $fotoIntegrante = !empty($p['foto_user']) ? zubbo_url('/'.ltrim((string)$p['foto_user'],'/')) : zubbo_url('/public/imagem/blank.png'); ?>
        <a class="grupo-integrante-perfil" href="../perfil/<?= (int)$p['id_user'] === $idUsuario ? 'perfil.php' : 'perfil-ver.php?id='.(int)$p['id_user'] ?>">
          <img src="<?= htmlspecialchars($fotoIntegrante,ENT_QUOTES,'UTF-8') ?>" alt="" loading="lazy">
          <span class="grupo-moderador-nome"><?= htmlspecialchars($p['nome_user'],ENT_QUOTES,'UTF-8') ?></span>
        </a>
        <?php if($p['moderador']): ?>
          <span class="grupo-moderador-badge">Moderador</span>
        <?php elseif($moderador): ?>
          <form action="promover-moderador.php" method="post">
            <?= zubbo_csrf_input() ?>
            <input type="hidden" name="id_grupo" value="<?= (int)$idGrupo ?>">
            <input type="hidden" name="id_user" value="<?= (int)$p['id_user'] ?>">
            <button type="submit" class="grupo-promover-btn" aria-label="Tornar <?= htmlspecialchars($p['nome_user'],ENT_QUOTES,'UTF-8') ?> moderador">Tornar moderador</button>
          </form>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="configuracoes-grupo grupo-config-card grupo-config-participacao">
    <h3>Participação</h3>
    <p>Gerencie sua participação neste grupo.</p>
    <form action="sair-grupo.php" method="post"
          onsubmit="return confirm('Tem certeza que deseja sair deste grupo?');">
      <?= zubbo_csrf_input() ?>
      <input type="hidden" name="id_conversa" value="<?= (int)$grupo['id_conversa'] ?>">
      <button type="submit" class="grupo-btn-sair">Sair do grupo</button>
    </form>
  </section>
</main>
<?php include __DIR__.'/../includes/under-bar.php';include __DIR__.'/../includes/footer.php'; ?>
