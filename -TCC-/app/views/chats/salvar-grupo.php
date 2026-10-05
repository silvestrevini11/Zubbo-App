<?php
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: criar-grupos.php');
    exit;
}

zubbo_require_csrf();

$id_usuario = (int) $_SESSION['usuario']['id'];
$nome = trim($_POST['nome_grupo'] ?? '');
$descricao = trim($_POST['descricao_grupo'] ?? '');
$participantes = array_values(array_unique(array_filter(array_map('intval', $_POST['participantes'] ?? []))));

if ($nome === '' || mb_strlen($nome) > 80) {
    $_SESSION['grupo_erro'] = 'Informe um nome válido para o grupo.';
    header('Location: criar-grupos.php');
    exit;
}
if (mb_strlen($descricao) > 255) {
    $_SESSION['grupo_erro'] = 'A descrição deve ter no máximo 255 caracteres.';
    header('Location: criar-grupos.php');
    exit;
}

$participantes = array_values(array_filter($participantes, fn($id) => $id > 0 && $id !== $id_usuario));
$permitidos = [];

if ($participantes) {
    $ph = implode(',', array_fill(0, count($participantes), '?'));
    $sql = "
        SELECT CASE WHEN id_user_1 = ? THEN id_user_2 ELSE id_user_1 END AS id_amigo
        FROM Amizade
        WHERE (id_user_1 = ? OR id_user_2 = ?)
          AND (CASE WHEN id_user_1 = ? THEN id_user_2 ELSE id_user_1 END) IN ($ph)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_usuario, $id_usuario, $id_usuario, $id_usuario, ...$participantes]);
    $permitidos = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$foto_grupo = null;
if (isset($_FILES['foto_grupo']) && $_FILES['foto_grupo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['foto_grupo']['error'] !== UPLOAD_ERR_OK || $_FILES['foto_grupo']['size'] > 4 * 1024 * 1024) {
        $_SESSION['grupo_erro'] = 'Não foi possível usar essa imagem.';
        header('Location: criar-grupos.php');
        exit;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto_grupo']['tmp_name']);
    $extensoes = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];

    if (!isset($extensoes[$mime]) || @getimagesize($_FILES['foto_grupo']['tmp_name']) === false) {
        $_SESSION['grupo_erro'] = 'A foto deve ser JPG, PNG ou WEBP.';
        header('Location: criar-grupos.php');
        exit;
    }

    $dirRel = 'public/uploads/grupos';
    $dirAbs = __DIR__ . '/../../../' . $dirRel;
    if (!is_dir($dirAbs)) mkdir($dirAbs, 0775, true);

    $arquivo = 'grupo_'.$id_usuario.'_'.bin2hex(random_bytes(8)).'.'.$extensoes[$mime];
    if (!move_uploaded_file($_FILES['foto_grupo']['tmp_name'], $dirAbs.'/'.$arquivo)) {
        $_SESSION['grupo_erro'] = 'Não foi possível salvar a foto do grupo.';
        header('Location: criar-grupos.php');
        exit;
    }
    $foto_grupo = $dirRel.'/'.$arquivo;
}

try {
    $conn->beginTransaction();

    $conn->prepare("INSERT INTO Conversa (tipo_conversa) VALUES ('grupo')")->execute();
    $id_conversa = (int)$conn->lastInsertId();

    $stmtGrupo = $conn->prepare("
        INSERT INTO Grupo (id_conversa, id_criador, nome_grupo, descricao_grupo, foto_grupo)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtGrupo->execute([$id_conversa, $id_usuario, $nome, $descricao ?: null, $foto_grupo]);

    $stmtPart = $conn->prepare("
        INSERT INTO Participantes_Conversa (id_user, id_conversa)
        VALUES (?, ?)
    ");
    $stmtPart->execute([$id_usuario, $id_conversa]);

    foreach ($permitidos as $id_amigo) {
        $stmtPart->execute([$id_amigo, $id_conversa]);
    }

    $conn->commit();
    header('Location: chats-grupos.php?criado=1');
    exit;
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    if ($foto_grupo && is_file(__DIR__.'/../../../'.$foto_grupo)) unlink(__DIR__.'/../../../'.$foto_grupo);
    $_SESSION['grupo_erro'] = 'Não foi possível criar o grupo. Tente novamente.';
    header('Location: criar-grupos.php');
    exit;
}