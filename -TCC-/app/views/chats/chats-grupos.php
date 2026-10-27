<?php
require_once __DIR__ . '/../../middleware/auth.php';
include __DIR__ . '/../includes/head.php';

$id_usuario = (int)$_SESSION['usuario']['id'];

$stmt = $conn->prepare("
    SELECT
        g.id_grupo,
        g.id_conversa,
        g.nome_grupo,
        g.descricao_grupo,
        g.foto_grupo,
        m.mensagem AS ultima_mensagem,
        m.data_envio,
        c.data_criacao
    FROM Grupo g
    INNER JOIN Conversa c ON c.id_conversa = g.id_conversa AND c.tipo_conversa = 'grupo'
    INNER JOIN Participantes_Conversa pc ON pc.id_conversa = g.id_conversa AND pc.id_user = :id_usuario
    LEFT JOIN Mensagem m ON m.id_mensagem = (
        SELECT MAX(m2.id_mensagem)
        FROM Mensagem m2
        WHERE m2.id_conversa = g.id_conversa
    )
    ORDER BY COALESCE(m.data_envio, c.data_criacao) DESC
");
$stmt->execute([':id_usuario'=>$id_usuario]);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
require __DIR__ . '/contadores-nao-lidas.php';

?>
<section class="chats-container">
    <button class="chats-btn-create" onclick="location.href='criar-grupos.php'" aria-label="Criar grupo">+</button>

    <h1 class="chats-titulo">Conversas</h1>

    <?php if (isset($_GET['saiu'])): ?>
        <div class="grupo-lista-sucesso" role="status">Você saiu do grupo.</div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['grupo_saida_erro'])): ?>
        <div class="grupo-criar-alerta" role="alert"><?= htmlspecialchars($_SESSION['grupo_saida_erro'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['grupo_saida_erro']); ?>
    <?php endif; ?>
    <?php if (isset($_GET['criado'])): ?>
        <div class="grupo-lista-sucesso">Grupo criado com sucesso.</div>
    <?php endif; ?>

    <div class="chats-options"></div>

    <div class="seletor-tipo">

    <span class="seletor-indicador"></span>

    <button
        class="opcao"
        data-tipo="privados"
        aria-label="Privados<?= $tiposComNaoLidas['privado'] ? ', com mensagens não lidas' : '' ?>"
        onclick="window.location.href='chats.php'"
    >
        Privados
        <?php if ($tiposComNaoLidas['privado']): ?>
            <span class="seletor-nao-lidas" aria-hidden="true"></span>
        <?php endif; ?>
    </button>

    <button
        class="opcao ativa"
        data-tipo="grupos"
        aria-label="Grupos<?= $tiposComNaoLidas['grupo'] ? ', com mensagens não lidas' : '' ?>"
        onclick="window.location.href='chats-grupos.php'"
    >
        Grupos
        <?php if ($tiposComNaoLidas['grupo']): ?>
            <span class="seletor-nao-lidas" aria-hidden="true"></span>
        <?php endif; ?>
    </button>

</div>

    <div class="chats-lista">
        <?php if (!$grupos): ?>
            <div class="grupo-lista-vazia">
                Você ainda não possui grupos.
            </div>
        <?php endif; ?>

        <?php foreach ($grupos as $grupo): ?>
            <?php
                $foto = $grupo['foto_grupo']
                    ? zubbo_url('/' . ltrim((string) $grupo['foto_grupo'], '/'))
                    : zubbo_url('/public/imagem/blank.png');

                $resumo = $grupo['ultima_mensagem']
                    ?: ($grupo['descricao_grupo'] ?: 'Grupo sem mensagens');
            ?>
            <a href="chat-grupo.php?id_conversa=<?= (int)$grupo['id_conversa'] ?>" class="chat-item grupo-chat-item">
                <img src="<?= htmlspecialchars($foto) ?>" alt="Foto do grupo" class="chat-item-foto">
                <div class="chat-item-info">
                    <strong><?= htmlspecialchars($grupo['nome_grupo']) ?></strong>
                    <span><?= htmlspecialchars($resumo) ?></span>
                </div>
                <?php $totalNaoLidas = (int) ($naoLidas[$grupo['id_conversa']] ?? 0); ?>
                <?php if ($totalNaoLidas > 0): ?>
                    <span class="chat-item-unread" aria-label="<?= $totalNaoLidas ?> mensagens não lidas"><?= $totalNaoLidas > 99 ? '99+' : $totalNaoLidas ?></span>
                <?php endif; ?>
            </a>
            <hr class="perfil-hr">
        <?php endforeach; ?>
    </div>
</section>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
