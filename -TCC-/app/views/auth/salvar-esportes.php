<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (!isset($_SESSION['cadastro_pendente'], $_SESSION['email_verificado'])) {
    header('Location: cadastro.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: escolher-esportes.php');
    exit;
}

zubbo_require_csrf();
require_once __DIR__ . '/../../../config/database.php';

$cadastro = $_SESSION['cadastro_pendente'];
$esportes = $_POST['esportes'] ?? [];

if (!is_array($esportes) || !$esportes) {
    header('Location: escolher-esportes.php?erro=nenhum');
    exit;
}

$esportes = array_values(array_unique(array_filter(array_map('intval', $esportes), static fn($id) => $id > 0)));

if (!$esportes) {
    header('Location: escolher-esportes.php?erro=nenhum');
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($esportes), '?'));
    $validar = $conn->prepare("SELECT id_esporte FROM Esporte WHERE id_esporte IN ($placeholders)");
    $validar->execute($esportes);
    $validos = array_map('intval', $validar->fetchAll(PDO::FETCH_COLUMN));

    sort($esportes);
    sort($validos);
    if ($esportes !== $validos) {
        throw new RuntimeException('Esporte inválido.');
    }

    $conn->beginTransaction();

    $stmt = $conn->prepare(
        'INSERT INTO Usuario
            (nome_user, email_user, tel_user, senha_user, date_user, email_verificado)
         VALUES (?, ?, ?, ?, ?, TRUE)'
    );
    $stmt->execute([
        $cadastro['nome'],
        $cadastro['email'],
        $cadastro['telefone'],
        $cadastro['senha'],
        $cadastro['data_nascimento'],
    ]);

    $idUsuario = (int) $conn->lastInsertId();
    $stmtEsporte = $conn->prepare(
        'INSERT INTO Usuario_Esporte (id_user, id_esporte) VALUES (?, ?)'
    );

    foreach ($esportes as $idEsporte) {
        $stmtEsporte->execute([$idUsuario, $idEsporte]);
    }

    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Erro ao concluir cadastro: ' . $e->getMessage());
    header('Location: escolher-esportes.php?erro=salvar');
    exit;
}

unset($_SESSION['cadastro_pendente'], $_SESSION['email_verificado']);
$_SESSION['sucesso_login'] = 'Cadastro concluído! Agora você pode entrar.';
header('Location: login.php', true, 303);
exit;
