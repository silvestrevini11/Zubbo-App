<?php
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: chats-grupos.php');
    exit;
}

zubbo_require_csrf();

$id_usuario = (int)$_SESSION['usuario']['id'];
$id_grupo = (int)($_POST['id_grupo'] ?? 0);
$nome = trim($_POST['nome_grupo'] ?? '');
$descricao = trim($_POST['descricao_grupo'] ?? '');
$remover = array_values(array_unique(array_filter(array_map('intval', $_POST['remover'] ?? []))));
$adicionar = array_values(array_unique(array_filter(array_map('intval', $_POST['adicionar'] ?? []))));

$stmt = $conn->prepare("SELECT * FROM Grupo WHERE id_grupo = ? AND id_criador = ?");
$stmt->execute([$id_grupo, $id_usuario]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: chats-grupos.php');
    exit;
}

function voltarEdicao($id_grupo, $mensagem) {
    $_SESSION['editar_grupo_erro'] = $mensagem;
    header('Location: editar-grupo.php?id_grupo='.$id_grupo);
    exit;
}

if ($nome === '' || mb_strlen($nome) > 80) {
    voltarEdicao($id_grupo, 'Informe um nome válido para o grupo.');
}
if (mb_strlen($descricao) > 255) {
    voltarEdicao($id_grupo, 'A descrição deve ter no máximo 255 caracteres.');
}

$remover = array_values(array_filter($remover, fn($id) => $id !== $id_usuario));
$permitidosAdicionar = [];

if ($adicionar) {
    $ph = implode(',', array_fill(0, count($adicionar), '?'));
    $stmt = $conn->prepare("
        SELECT CASE WHEN id_user_1 = ? THEN id_user_2 ELSE id_user_1 END AS id_amigo
        FROM Amizade
        WHERE (id_user_1 = ? OR id_user_2 = ?)
          AND (CASE WHEN id_user_1 = ? THEN id_user_2 ELSE id_user_1 END) IN ($ph)
    ");
    $stmt->execute([$id_usuario, $id_usuario, $id_usuario, $id_usuario, ...$adicionar]);
    $permitidosAdicionar = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$novaFoto = null;
$arquivoNovo = null;

if (isset($_FILES['foto_grupo']) && $_FILES['foto_grupo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['foto_grupo']['error'] !== UPLOAD_ERR_OK || $_FILES['foto_grupo']['size'] > 4*1024*1024) {
        voltarEdicao($id_grupo, 'Não foi possível usar essa imagem.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto_grupo']['tmp_name']);
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

    if (!isset($ext[$mime]) || @getimagesize($_FILES['foto_grupo']['tmp_name']) === false) {
        voltarEdicao($id_grupo, 'A foto deve ser JPG, PNG ou WEBP.');
    }

    $dirRel = 'public/uploads/grupos';
    $dirAbs = __DIR__.'/../../../'.$dirRel;
    if (!is_dir($dirAbs)) mkdir($dirAbs, 0775, true);

    $arquivo = 'grupo_'.$id_usuario.'_'.bin2hex(random_bytes(8)).'.'.$ext[$mime];
    $arquivoNovo = $dirAbs.'/'.$arquivo;

    if (!move_uploaded_file($_FILES['foto_grupo']['tmp_name'], $arquivoNovo)) {
        voltarEdicao($id_grupo, 'Não foi possível salvar a nova foto.');
    }

    $novaFoto = $dirRel.'/'.$arquivo;
}

try {
    $conn->beginTransaction();

    if ($novaFoto) {
        $stmt = $conn->prepare("
            UPDATE Grupo
            SET nome_grupo = ?, descricao_grupo = ?, foto_grupo = ?
            WHERE id_grupo = ? AND id_criador = ?
        ");
        $stmt->execute([$nome, $descricao ?: null, $novaFoto, $id_grupo, $id_usuario]);
    } else {
        $stmt = $conn->prepare("
            UPDATE Grupo
            SET nome_grupo = ?, descricao_grupo = ?
            WHERE id_grupo = ? AND id_criador = ?
        ");
        $stmt->execute([$nome, $descricao ?: null, $id_grupo, $id_usuario]);
    }

    if ($remover) {
        $ph = implode(',', array_fill(0, count($remover), '?'));
        $stmt = $conn->prepare("
            DELETE FROM Participantes_Conversa
            WHERE id_conversa = ?
              AND id_user <> ?
              AND id_user IN ($ph)
        ");
        $stmt->execute([$grupo['id_conversa'], $id_usuario, ...$remover]);
    }

    $stmtAdd = $conn->prepare("
        INSERT IGNORE INTO Participantes_Conversa (id_user, id_conversa)
        VALUES (?, ?)
    ");
    foreach ($permitidosAdicionar as $id_amigo) {
        $stmtAdd->execute([$id_amigo, $grupo['id_conversa']]);
    }

    $conn->commit();

    if ($novaFoto && !empty($grupo['foto_grupo'])) {
        $antiga = __DIR__.'/../../../'.$grupo['foto_grupo'];
        if (is_file($antiga)) unlink($antiga);
    }

    $_SESSION['editar_grupo_sucesso'] = 'Grupo atualizado com sucesso.';
    header('Location: editar-grupo.php?id_grupo='.$id_grupo);
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    if ($arquivoNovo && is_file($arquivoNovo)) unlink($arquivoNovo);
    voltarEdicao($id_grupo, 'Não foi possível salvar as alterações.');
}