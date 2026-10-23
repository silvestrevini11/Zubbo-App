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
<main class="configuracoes" style="padding:24px 16px 110px;max-width:680px;margin:0 auto">
  <p><a href="chat-grupo.php?id_conversa=<?= (int)$grupo['id_conversa'] ?>">← Voltar ao grupo</a></p>
  <h1>Configurações do grupo</h1>
  <h2><?= htmlspecialchars($grupo['nome_grupo'],ENT_QUOTES,'UTF-8') ?></h2>
  <p><?= (int)$grupo['quantidade'] ?> participantes ativos</p>
  <section class="configuracoes-grupo">
    <h3>Organizar encontro esportivo</h3>
    <p>Crie um evento e convide todos os participantes do grupo. Cada pessoa poderá confirmar a própria participação no evento.</p>
    <a class="configuracoes-item configuracoes-link"
       href="../eventos/criar-evento.php?id_grupo=<?= (int)$grupo['id_grupo'] ?>">Criar evento e convidar o grupo →</a>
  </section>
  <?php if($criador): ?>
  <section class="configuracoes-grupo">
    <h3>Administração do grupo</h3>
    <a class="configuracoes-item configuracoes-link"
       href="editar-grupo.php?id_grupo=<?= (int)$grupo['id_grupo'] ?>">Editar nome, foto e participantes →</a>
    <p>O criador não pode sair diretamente pelo botão padrão. Use a edição do grupo para gerenciar participantes.</p>
  </section>
  <?php else: ?>
  <section class="configuracoes-grupo">
    <h3>Participação</h3>
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
