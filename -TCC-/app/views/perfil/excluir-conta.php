<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Solicitação inválida.');
}

require __DIR__ . '/../../../config/database.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$senha = $_POST['senha'] ?? '';

$consulta = $conn->prepare('SELECT senha_user FROM Usuario WHERE id_user = ?');
$consulta->execute([$idUsuario]);
$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($senha, $usuario['senha_user'])) {
    header('Location: perfil-configuracoes.php?erro=senha');
    exit;
}

try {
    $conn->beginTransaction();

    // Remove primeiro os registros que apontam diretamente para o usuário.
    $dependencias = $conn->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'Usuario'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dependencias as $dependencia) {
        $tabela = $dependencia['TABLE_NAME'];
        $coluna = $dependencia['COLUMN_NAME'];
        if ($tabela !== 'Usuario' && preg_match('/^[A-Za-z0-9_]+$/', $tabela) && preg_match('/^[A-Za-z0-9_]+$/', $coluna)) {
            $conn->prepare("DELETE FROM `$tabela` WHERE `$coluna` = ?")->execute([$idUsuario]);
        }
    }

    $conn->prepare('DELETE FROM Usuario WHERE id_user = ?')->execute([$idUsuario]);
    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header('Location: perfil-configuracoes.php?erro=exclusao');
    exit;
}

$_SESSION = [];
session_destroy();
header('Location: ../auth/login.php?conta=excluida');
exit;
