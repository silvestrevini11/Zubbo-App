<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../config/database.php';

$contagens = [];
$consultas = [
    'usuarios' => "SELECT COUNT(*) FROM Usuario",
    'usuarios_ativos' => "SELECT COUNT(*) FROM Usuario WHERE status_user = 'ativo'",
    'eventos' => "SELECT COUNT(*) FROM Evento WHERE status_evento = 'ativo'",
    'denuncias' => "SELECT COUNT(*) FROM Denuncia WHERE status_denuncia IN ('pendente','em_analise')",
    'sugestoes' => "SELECT COUNT(*) FROM Sugestao_Esporte WHERE status_sugestao = 'pendente'",
    'locais' => "SELECT COUNT(*) FROM LocalEsp WHERE status_local = 'pendente'",
    'mensagens' => "SELECT COUNT(*) FROM Mensagem",
];
foreach ($consultas as $chave => $sql) {
    $contagens[$chave] = (int) $conn->query($sql)->fetchColumn();
}

$ultimosUsuarios = $conn->query("SELECT id_user, nome_user, email_user, status_user FROM Usuario ORDER BY id_user DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$ultimasAcoes = $conn->query("
    SELECT aa.tipo_acao, aa.motivo, aa.data_acao, a.nome_adm
    FROM Acao_Administrativa aa
    INNER JOIN Administrador a ON a.id_adm = aa.id_adm
    ORDER BY aa.data_acao DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$paginaAdmin = 'painel';
$tituloAdmin = 'Visão geral';
require __DIR__ . '/includes/header.php';
?>
<div class="admin-cards">
    <a class="admin-card" href="usuarios.php"><span class="admin-card-icon">♟</span><div><small>Usuários cadastrados</small><strong><?= $contagens['usuarios'] ?></strong><em><?= $contagens['usuarios_ativos'] ?> ativos</em></div></a>
    <a class="admin-card" href="eventos.php"><span class="admin-card-icon">◫</span><div><small>Eventos ativos</small><strong><?= $contagens['eventos'] ?></strong><em>publicados no sistema</em></div></a>
    <a class="admin-card admin-card-alerta" href="denuncias.php"><span class="admin-card-icon">!</span><div><small>Denúncias abertas</small><strong><?= $contagens['denuncias'] ?></strong><em>aguardando moderação</em></div></a>
    <a class="admin-card" href="sugestoes.php"><span class="admin-card-icon">＋</span><div><small>Sugestões pendentes</small><strong><?= $contagens['sugestoes'] ?></strong><em>novos esportes</em></div></a>
    <a class="admin-card" href="locais.php"><span class="admin-card-icon">⌖</span><div><small>Locais pendentes</small><strong><?= $contagens['locais'] ?></strong><em>para aprovação</em></div></a>
    <div class="admin-card"><span class="admin-card-icon">✉</span><div><small>Mensagens enviadas</small><strong><?= $contagens['mensagens'] ?></strong><em>no total</em></div></div>
</div>

<div class="admin-grid-2">
    <section class="admin-panel">
        <div class="admin-panel-head"><div><p>CADASTROS</p><h2>Usuários recentes</h2></div><a href="usuarios.php">Ver todos</a></div>
        <div class="admin-lista-simples">
            <?php if (!$ultimosUsuarios): ?><p class="admin-vazio">Nenhum usuário cadastrado.</p><?php endif; ?>
            <?php foreach ($ultimosUsuarios as $u): ?>
                <div class="admin-lista-item">
                    <span class="admin-avatar"><?= htmlspecialchars(mb_substr($u['nome_user'], 0, 1)) ?></span>
                    <div class="admin-lista-info"><strong><?= htmlspecialchars($u['nome_user']) ?></strong><small><?= htmlspecialchars($u['email_user']) ?></small></div>
                    <span class="admin-status status-<?= htmlspecialchars($u['status_user']) ?>"><?= htmlspecialchars($u['status_user']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><p>MODERAÇÃO</p><h2>Últimas ações</h2></div><a href="acoes.php">Histórico</a></div>
        <div class="admin-lista-simples">
            <?php if (!$ultimasAcoes): ?><p class="admin-vazio">Ainda não existem ações administrativas.</p><?php endif; ?>
            <?php foreach ($ultimasAcoes as $acao): ?>
                <div class="admin-acao-resumo">
                    <span>✓</span><div><strong><?= htmlspecialchars($acao['tipo_acao']) ?></strong><p><?= htmlspecialchars($acao['motivo']) ?></p><small><?= date('d/m/Y H:i', strtotime($acao['data_acao'])) ?> · <?= htmlspecialchars($acao['nome_adm']) ?></small></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
