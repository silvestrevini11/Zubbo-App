<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../config/database.php';

$busca = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$permitidos = ['ativo', 'suspenso', 'banido'];

$sql = "
    SELECT u.id_user, u.nome_user, u.email_user, u.tel_user, u.date_user, u.email_verificado, u.status_user,
           COUNT(DISTINCT ue.id_esporte) AS qtd_esportes
    FROM Usuario u
    LEFT JOIN Usuario_Esporte ue ON ue.id_user = u.id_user
    WHERE 1=1
";
$params = [];
if ($busca !== '') {
    $sql .= " AND (u.nome_user LIKE ? OR u.email_user LIKE ? OR u.tel_user LIKE ?)";
    $termo = '%' . $busca . '%';
    array_push($params, $termo, $termo, $termo);
}
if (in_array($status, $permitidos, true)) {
    $sql .= " AND u.status_user = ?";
    $params[] = $status;
}
$sql .= " GROUP BY u.id_user ORDER BY u.id_user DESC LIMIT 200";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$paginaAdmin = 'usuarios';
$tituloAdmin = 'Gerenciar usuários';
require __DIR__ . '/includes/header.php';
?>
<form class="admin-filtros" method="get">
    <input type="search" name="q" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por nome, e-mail ou telefone">
    <select name="status">
        <option value="">Todos os status</option>
        <?php foreach ($permitidos as $op): ?><option value="<?= $op ?>" <?= $status === $op ? 'selected' : '' ?>><?= ucfirst($op) ?></option><?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
    <a href="usuarios.php">Limpar</a>
</form>

<section class="admin-panel admin-table-wrap">
    <div class="admin-panel-head"><div><p>CONTAS</p><h2><?= count($usuarios) ?> usuário(s)</h2></div></div>
    <table class="admin-table">
        <thead><tr><th>Usuário</th><th>Telefone</th><th>Nascimento</th><th>Esportes</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if (!$usuarios): ?><tr><td colspan="6" class="admin-vazio">Nenhum usuário encontrado.</td></tr><?php endif; ?>
        <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><div class="admin-user-cell"><span class="admin-avatar"><?= htmlspecialchars(mb_substr($u['nome_user'], 0, 1)) ?></span><div><strong><?= htmlspecialchars($u['nome_user']) ?></strong><small>#<?= (int)$u['id_user'] ?> · <?= htmlspecialchars($u['email_user']) ?><?= $u['email_verificado'] ? ' · verificado' : '' ?></small></div></div></td>
                <td><?= htmlspecialchars($u['tel_user']) ?></td>
                <td><?= date('d/m/Y', strtotime($u['date_user'])) ?></td>
                <td><?= (int)$u['qtd_esportes'] ?></td>
                <td><span class="admin-status status-<?= htmlspecialchars($u['status_user']) ?>"><?= htmlspecialchars($u['status_user']) ?></span></td>
                <td>
                    <form class="admin-acoes-inline" action="usuario-acao.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                        <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                        <select name="acao" aria-label="Ação para <?= htmlspecialchars($u['nome_user']) ?>">
                            <option value="ativo">Ativar</option>
                            <option value="suspenso">Suspender</option>
                            <option value="banido">Banir</option>
                        </select>
                        <input type="text" name="motivo" maxlength="255" placeholder="Motivo" required>
                        <button type="submit">Aplicar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
