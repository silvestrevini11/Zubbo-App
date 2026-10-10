<?php
require_once __DIR__ . '/_auth.php';
admin_exigir_post();
$id = filter_var($_POST['id_evento'] ?? null, FILTER_VALIDATE_INT);
$acao = (string) ($_POST['acao'] ?? '');
$motivo = trim((string) ($_POST['motivo'] ?? ''));
if (!$id || !in_array($acao, ['ativo', 'cancelado', 'removido'], true) || $motivo === '' || mb_strlen($motivo) > 255) {
    admin_flash('erro', 'Dados inválidos para alterar o evento.');
    header('Location: eventos.php'); exit;
}
try {
    $conn->beginTransaction();
    $st = $conn->prepare('UPDATE Evento SET status_evento=? WHERE id_evento=?');
    $st->execute([$acao, $id]);
    if ($st->rowCount() === 0) {
        $check = $conn->prepare('SELECT 1 FROM Evento WHERE id_evento=?');
        $check->execute([$id]);
        if (!$check->fetchColumn()) throw new RuntimeException('event_missing');
    }
    admin_registrar_acao($conn, 'evento_' . $acao, $motivo, ['id_evento' => (int) $id]);
    $conn->commit();
    admin_flash('sucesso', 'Evento atualizado.');
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    admin_flash('erro', 'Não foi possível atualizar o evento.');
}
header('Location: eventos.php', true, 303);
exit;
