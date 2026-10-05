<?php
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

zubbo_require_csrf();

$idUser = (int) $_SESSION['usuario']['id'];
$arquivo = $_FILES['fotoPerfil'] ?? null;

if (
    !is_array($arquivo)
    || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
    || (int) ($arquivo['size'] ?? 0) <= 0
    || (int) ($arquivo['size'] ?? 0) > 4 * 1024 * 1024
) {
    header('Location: perfil.php');
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$tipo = $finfo->file((string) $arquivo['tmp_name']);
$tiposPermitidos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (!isset($tiposPermitidos[$tipo]) || @getimagesize((string) $arquivo['tmp_name']) === false) {
    http_response_code(400);
    exit('Imagem inválida.');
}

$pasta = __DIR__ . '/../../../public/uploads/perfis/';
if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
    http_response_code(500);
    exit('Não foi possível preparar o diretório de upload.');
}

$nomeArquivo = 'perfil_' . $idUser . '_' . bin2hex(random_bytes(12)) . '.' . $tiposPermitidos[$tipo];
$caminhoCompleto = $pasta . $nomeArquivo;

$stmt = $conn->prepare('SELECT foto_user FROM Usuario WHERE id_user = ? LIMIT 1');
$stmt->execute([$idUser]);
$fotoAnterior = (string) ($stmt->fetchColumn() ?: '');

if (!move_uploaded_file((string) $arquivo['tmp_name'], $caminhoCompleto)) {
    http_response_code(500);
    exit('Não foi possível salvar a imagem.');
}

$caminhoBanco = 'public/uploads/perfis/' . $nomeArquivo;

try {
    $stmt = $conn->prepare('UPDATE Usuario SET foto_user = ? WHERE id_user = ?');
    $stmt->execute([$caminhoBanco, $idUser]);
} catch (Throwable $e) {
    @unlink($caminhoCompleto);
    error_log('Falha ao atualizar foto de perfil: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível atualizar a foto.');
}

if (str_starts_with($fotoAnterior, 'public/uploads/perfis/')) {
    $antiga = __DIR__ . '/../../../' . $fotoAnterior;
    if (is_file($antiga) && realpath(dirname($antiga)) === realpath($pasta)) {
        @unlink($antiga);
    }
}

header('Location: perfil.php', true, 303);
exit;
