<?php
require_once __DIR__ . '/_auth.php';
admin_exigir_post();

$id = filter_var($_POST['id_user'] ?? null, FILTER_VALIDATE_INT);
$acao = (string) ($_POST['acao'] ?? '');
$motivo = trim((string) ($_POST['motivo'] ?? ''));
if (!$id || !in_array($acao, ['ativo', 'suspenso', 'banido'], true) || $motivo === '' || mb_strlen($motivo) > 255) {
    admin_flash('erro', 'Dados inválidos para alterar o usuário.');
    header('Location: usuarios.php'); exit;
}
if ((int)$id === (int)$_SESSION['usuario']['id'] && $acao !== 'ativo') {
    admin_flash('erro', 'Você não pode suspender sua própria conta administrativa.');
    header('Location: usuarios.php'); exit;
}
try {
    $conn->beginTransaction();
    $stmt = $conn->prepare('SELECT id_user FROM Usuario WHERE id_user=? FOR UPDATE');
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) throw new RuntimeException('user_missing');
    $stmt = $conn->prepare('SELECT 1 FROM Administrador WHERE id_user=? AND ativo=1 LIMIT 1');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() && $acao !== 'ativo') throw new RuntimeException('active_admin');
    $conn->prepare('UPDATE Usuario SET status_user = ? WHERE id_user = ?')->execute([$acao, $id]);
    admin_registrar_acao($conn, 'usuario_' . $acao, $motivo, ['id_user' => (int) $id]);
    $conn->commit();
    admin_flash('sucesso', 'Status do usuário atualizado.');
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    admin_flash('erro', 'Não foi possível atualizar o usuário. Contas administrativas ativas não podem ser suspensas por este painel.');
}
header('Location: usuarios.php', true, 303);
exit;
