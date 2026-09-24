<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil-esportes-editar.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$tokenRecebido = $_POST['csrf_token'] ?? '';
$tokenSessao = $_SESSION['csrf_token'] ?? '';

if ($tokenSessao === '' || $tokenRecebido === '' || !hash_equals($tokenSessao, $tokenRecebido)) {
    header('Location: perfil-esportes-editar.php?erro=csrf');
    exit;
}

$id_user = (int) $_SESSION['usuario']['id'];
$esportes = $_POST['esportes'] ?? [];

if (!is_array($esportes) || empty($esportes)) {
    header('Location: perfil-esportes-editar.php?erro=nenhum');
    exit;
}

$esportes = array_values(array_unique(array_filter(array_map('intval', $esportes), fn($id) => $id > 0)));

if (empty($esportes)) {
    header('Location: perfil-esportes-editar.php?erro=nenhum');
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($esportes), '?'));
    $stmtValidos = $conn->prepare("SELECT id_esporte FROM Esporte WHERE id_esporte IN ($placeholders)");
    $stmtValidos->execute($esportes);
    $idsValidos = array_map('intval', $stmtValidos->fetchAll(PDO::FETCH_COLUMN));

    sort($esportes);
    sort($idsValidos);

    if ($esportes !== $idsValidos) {
        header('Location: perfil-esportes-editar.php?erro=salvar');
        exit;
    }

    $conn->beginTransaction();

    $stmtExcluir = $conn->prepare('DELETE FROM Usuario_Esporte WHERE id_user = ?');
    $stmtExcluir->execute([$id_user]);

    $stmtInserir = $conn->prepare('INSERT INTO Usuario_Esporte (id_user, id_esporte) VALUES (?, ?)');
    foreach ($esportes as $id_esporte) {
        $stmtInserir->execute([$id_user, $id_esporte]);
    }

    $conn->commit();

    header('Location: perfil-editar.php?sucesso=esportes');
    exit;
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Location: perfil-esportes-editar.php?erro=salvar');
    exit;
}
