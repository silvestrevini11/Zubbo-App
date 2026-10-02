<?php
session_start();
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}
include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../../../config/database.php';

$id_usuario = (int)$_SESSION['usuario']['id'];

$stmt = $conn->prepare("
    SELECT
        g.id_grupo,
        g.id_conversa,
        g.nome_grupo,
        g.descricao_grupo,
        g.foto_grupo,
        COUNT(DISTINCT pc2.id_user) AS total_participantes,
        m.mensagem AS ultima_mensagem,
        m.data_envio,
        c.data_criacao
    FROM Grupo g
    INNER JOIN Conversa c ON c.id_conversa = g.id_conversa AND c.tipo_conversa = 'grupo'
    INNER JOIN Participantes_Conversa pc ON pc.id_conversa = g.id_conversa AND pc.id_user = :id_usuario
    LEFT JOIN Participantes_Conversa pc2 ON pc2.id_conversa = g.id_conversa
    LEFT JOIN Mensagem m ON m.id_mensagem = (
        SELECT MAX(m2.id_mensagem)
        FROM Mensagem m2
        WHERE m2.id_conversa = g.id_conversa
    )
    GROUP BY g.id_grupo, g.id_conversa, g.nome_grupo, g.descricao_grupo,
             g.foto_grupo, m.id_mensagem, m.mensagem, m.data_envio, c.data_criacao
    ORDER BY COALESCE(m.data_envio, c.data_criacao) DESC
");
$stmt->execute([':id_usuario'=>$id_usuario]);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="chats-container">
    <button class="chats-btn-create" onclick="location.href='criar-grupos.php'" aria-label="Criar grupo">+</button>

    <h1 class="chats-titulo">Conversas</h1>

    <?php if (isset($_GET['criado'])): ?>
        <div class="grupo-lista-sucesso">Grupo criado com sucesso.</div>
    <?php endif; ?>

    <div class="chats-options"></div>

    <div class="seletor-tipo">
        <span class="seletor-indicador"></span>
        <button class="opcao" data-tipo="privados" onclick="location.href='chats.php'">Privados</button>
        <button class="opcao ativa" data-tipo="grupos" onclick="location.href='chats-grupos.php'">Grupos</button>
        <button class="opcao" data-tipo="comunidade" onclick="location.href='chats-comunidade.php'">Comunidade</button>
    </div>

    <div class="chats-lista">
        <?php if (!$grupos): ?>
            <div class="grupo-lista-vazia">
                <strong>Nenhum grupo por aqui ainda.</strong>
                <span>Toque no botão + para criar o primeiro.</span>
            </div>
        <?php endif; ?>

        <?php foreach ($grupos as $grupo): ?>
            <?php
                $foto = $grupo['foto_grupo']
                    ? '/-TCC-/'.$grupo['foto_grupo']
                    : '/-TCC-/public/imagem/blank.png';

                $resumo = $grupo['ultima_mensagem']
                    ?: ($grupo['descricao_grupo'] ?: $grupo['total_participantes'].' participantes');
            ?>
            <a href="chat-grupo.php?id_conversa=<?= (int)$grupo['id_conversa'] ?>" class="chat-item grupo-chat-item">
                <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do grupo" class="chat-item-foto">
                <div class="chat-item-info">
                    <strong><?= htmlspecialchars($grupo['nome_grupo']) ?></strong>
                    <span><?= htmlspecialchars($resumo) ?></span>
                </div>
                <span class="grupo-chat-total"><?= (int)$grupo['total_participantes'] ?></span>
            </a>
            <hr class="perfil-hr">
        <?php endforeach; ?>
    </div>
</section>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>