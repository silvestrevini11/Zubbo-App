<?php
require_once __DIR__ . '/_auth.php';
admin_exigir_post();
$id = filter_var($_POST['id_denuncia'] ?? null, FILTER_VALIDATE_INT);
$acao = (string) ($_POST['acao'] ?? '');
$motivo = trim((string) ($_POST['motivo'] ?? ''));
if (!$id || !in_array($acao, ['pendente', 'em_analise', 'resolvida', 'rejeitada'], true) || $motivo === '' || mb_strlen($motivo) > 255) {
    admin_flash('erro', 'Dados inválidos para alterar a denúncia.');
    header('Location: denuncias.php'); exit;
}
$dataAnalise = in_array($acao, ['resolvida', 'rejeitada'], true) ? gmdate('Y-m-d H:i:s') : null;
try {
    $conn->beginTransaction();
    $st = $conn->prepare('UPDATE Denuncia SET status_denuncia=?, id_adm=?, data_analise=? WHERE id_denuncia=?');
    $st->execute([$acao, (int) $_SESSION['admin']['id'], $dataAnalise, $id]);
    if ($st->rowCount() === 0) {
        $check = $conn->prepare('SELECT 1 FROM Denuncia WHERE id_denuncia=?');
        $check->execute([$id]);
        if (!$check->fetchColumn()) throw new RuntimeException('report_missing');
    }
    admin_registrar_acao($conn, 'denuncia_' . $acao, $motivo, ['id_denuncia' => (int) $id]);
    $conn->commit();
    admin_flash('sucesso', 'Denúncia atualizada.');
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    admin_flash('erro', 'Não foi possível atualizar a denúncia.');
}
header('Location: denuncias.php', true, 303);
exit;
