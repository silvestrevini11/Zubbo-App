<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../config/database.php';

$status = $_GET['status'] ?? '';
$permitidos = ['ativo','cancelado','removido'];
$sql = "
    SELECT ev.id_evento, ev.nome_evento, ev.data_evento, ev.horario_evento, ev.status_evento,
           e.nome_esporte, l.nome_local, u.nome_user AS criador,
           COUNT(DISTINCT le.id_user) AS participantes
    FROM Evento ev
    INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
    INNER JOIN LocalEsp l ON l.id_local = ev.id_local
    LEFT JOIN Usuario u ON u.id_user = ev.id_criador
    LEFT JOIN Lista_Evento le ON le.id_evento = ev.id_evento
";
$params = [];
if (in_array($status, $permitidos, true)) { $sql .= ' WHERE ev.status_evento = ?'; $params[] = $status; }
$sql .= ' GROUP BY ev.id_evento ORDER BY ev.data_evento DESC, ev.horario_evento DESC LIMIT 200';
$stmt = $conn->prepare($sql); $stmt->execute($params); $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$paginaAdmin='eventos'; $tituloAdmin='Gerenciar eventos'; require __DIR__.'/includes/header.php';
?>
<form class="admin-filtros" method="get"><select name="status"><option value="">Todos os status</option><?php foreach($permitidos as $op): ?><option value="<?= $op ?>" <?= $status===$op?'selected':'' ?>><?= ucfirst($op) ?></option><?php endforeach; ?></select><button type="submit">Filtrar</button><a href="eventos.php">Limpar</a></form>
<section class="admin-panel admin-table-wrap"><div class="admin-panel-head"><div><p>AGENDA</p><h2><?= count($eventos) ?> evento(s)</h2></div></div><table class="admin-table"><thead><tr><th>Evento</th><th>Esporte/local</th><th>Data</th><th>Participantes</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php if(!$eventos): ?><tr><td colspan="6" class="admin-vazio">Nenhum evento encontrado.</td></tr><?php endif; ?>
<?php foreach($eventos as $ev): ?><tr><td><strong><?= htmlspecialchars($ev['nome_evento']) ?></strong><small class="admin-cell-sub">#<?= (int)$ev['id_evento'] ?> · por <?= htmlspecialchars($ev['criador'] ?? 'usuário removido') ?></small></td><td><?= htmlspecialchars($ev['nome_esporte']) ?><small class="admin-cell-sub"><?= htmlspecialchars($ev['nome_local']) ?></small></td><td><?= date('d/m/Y',strtotime($ev['data_evento'])) ?><small class="admin-cell-sub"><?= substr($ev['horario_evento'],0,5) ?></small></td><td><?= (int)$ev['participantes'] ?></td><td><span class="admin-status status-<?= htmlspecialchars($ev['status_evento']) ?>"><?= htmlspecialchars($ev['status_evento']) ?></span></td><td><form class="admin-acoes-inline" action="evento-acao.php" method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>"><input type="hidden" name="id_evento" value="<?= (int)$ev['id_evento'] ?>"><select name="acao"><option value="ativo">Ativar</option><option value="cancelado">Cancelar</option><option value="removido">Remover</option></select><input name="motivo" maxlength="255" placeholder="Motivo" required><button>Aplicar</button></form></td></tr><?php endforeach; ?>
</tbody></table></section>
<?php require __DIR__.'/includes/footer.php'; ?>
