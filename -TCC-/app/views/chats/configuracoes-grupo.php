<?php
require_once __DIR__.'/../../middleware/auth.php';
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
$criador=(int)$grupo['id_criador']===$idUsuario;
include __DIR__.'/../includes/head.php';
?>
<main class="configuracoes grupo-configuracoes">
  <a class="grupo-config-voltar" href="chat-grupo.php?id_conversa=<?= (int)$grupo['id_conversa'] ?>"><span aria-hidden="true">←</span> Voltar ao grupo</a>
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
  <section class="configuracoes-grupo grupo-config-card">
    <h3>Organizar encontro esportivo</h3>
    <p>Crie um evento e convide todos os participantes do grupo. Cada pessoa poderá confirmar a própria participação no evento.</p>
    <a class="grupo-config-acao grupo-config-acao-principal"
       href="../eventos/criar-evento.php?id_grupo=<?= (int)$grupo['id_grupo'] ?>"><span>Criar evento e convidar o grupo</span><span aria-hidden="true">→</span></a>
  </section>
  <?php if($criador): ?>
  <section class="configuracoes-grupo grupo-config-card">
    <h3>Administração do grupo</h3>
    <p>O criador não pode sair diretamente pelo botão padrão. Use a edição do grupo para gerenciar participantes.</p>
    <a class="grupo-config-acao"
       href="editar-grupo.php?id_grupo=<?= (int)$grupo['id_grupo'] ?>"><span>Editar nome, foto e participantes</span><span aria-hidden="true">→</span></a>
  </section>
  <?php else: ?>
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
  <?php endif; ?>
</main>
<?php include __DIR__.'/../includes/under-bar.php';include __DIR__.'/../includes/footer.php'; ?>
