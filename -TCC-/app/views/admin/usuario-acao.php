<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../config/database.php';
admin_exigir_post();

$idUser = (int) ($_POST['id_user'] ?? 0);
$acao = $_POST['acao'] ?? '';
$motivo = trim($_POST['motivo'] ?? '');
$permitidos = ['ativo', 'suspenso', 'banido'];

if ($idUser <= 0 || !in_array($acao, $permitidos, true) || $motivo === '') {
    admin_flash('erro', 'Dados inválidos para alterar o usuário.');
    header('Location: usuarios.php'); exit;
}

$stmt = $conn->prepare('UPDATE Usuario SET status_user = ? WHERE id_user = ?');
$stmt->execute([$acao, $idUser]);
admin_registrar_acao($conn, 'usuario_' . $acao, $motivo, ['id_user' => $idUser]);
admin_flash('sucesso', 'Status do usuário atualizado.');
header('Location: usuarios.php');
exit;
