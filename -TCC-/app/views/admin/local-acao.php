<?php
require_once __DIR__ . '/_auth.php';
admin_exigir_post();
$id = filter_var($_POST['id_local'] ?? null, FILTER_VALIDATE_INT);
$acao = (string) ($_POST['acao'] ?? '');
$motivo = trim((string) ($_POST['motivo'] ?? ''));
if (!$id || !in_array($acao, ['pendente', 'aprovado', 'rejeitado'], true) || $motivo === '' || mb_strlen($motivo) > 255) {
    admin_flash('erro', 'Dados inválidos para alterar o local.');
    header('Location: locais.php'); exit;
}
try {
    $conn->beginTransaction();
    $st = $conn->prepare('UPDATE LocalEsp SET status_local=? WHERE id_local=?');
    $st->execute([$acao, $id]);
    if ($st->rowCount() === 0) {
        $check = $conn->prepare('SELECT 1 FROM LocalEsp WHERE id_local=?');
        $check->execute([$id]);
        if (!$check->fetchColumn()) throw new RuntimeException('local_missing');
    }
    admin_registrar_acao($conn, 'local_' . $acao, $motivo, ['id_local' => (int) $id]);
    $conn->commit();
    admin_flash('sucesso', 'Local atualizado.');
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    admin_flash('erro', 'Não foi possível atualizar o local.');
}
header('Location: locais.php', true, 303);
exit;
