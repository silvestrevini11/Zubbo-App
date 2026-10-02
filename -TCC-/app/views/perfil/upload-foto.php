<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
include __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$id_user = (int) $_SESSION['usuario']['id'];

if (!isset($_FILES['fotoPerfil'])) {
    header('Location: perfil.php');
    exit;
}

$arquivo = $_FILES['fotoPerfil'];

if (
    $arquivo['error'] !== UPLOAD_ERR_OK
    || ($arquivo['size'] ?? 0) <= 0
    || ($arquivo['size'] ?? 0) > 4 * 1024 * 1024
) {
    header('Location: perfil.php');
    exit;
}

$tiposPermitidos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

$tipo = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);

if (!isset($tiposPermitidos[$tipo]) || @getimagesize($arquivo['tmp_name']) === false) {
    http_response_code(415);
    exit('Tipo de imagem não permitido.');
}

$pasta = __DIR__ . '/../../../public/uploads/perfis/';
if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
    http_response_code(500);
    exit('Não foi possível preparar o diretório de upload.');
}

$extensao = $tiposPermitidos[$tipo];
$nomeArquivo = 'perfil_' . $id_user . '_' . bin2hex(random_bytes(8)) . '.' . $extensao;
$caminhoCompleto = $pasta . $nomeArquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
    http_response_code(500);
    exit('Não foi possível salvar a imagem.');
}

$caminhoBanco = 'public/uploads/perfis/' . $nomeArquivo;
$stmt = $conn->prepare('UPDATE Usuario SET foto_user = ? WHERE id_user = ?');
$stmt->execute([$caminhoBanco, $id_user]);

header('Location: perfil.php');
exit;
