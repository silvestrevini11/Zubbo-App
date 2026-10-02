<?php
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

zubbo_require_csrf();

$idUsuario = (int) $_SESSION['usuario']['id'];

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
$nomeArquivo = 'perfil_' . $idUsuario . '_' . bin2hex(random_bytes(8)) . '.' . $extensao;
$caminhoCompleto = $pasta . $nomeArquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
    http_response_code(500);
    exit('Não foi possível salvar a imagem.');
}

$caminhoBanco = 'public/uploads/perfis/' . $nomeArquivo;

$stmtAntiga = $conn->prepare('SELECT foto_user FROM Usuario WHERE id_user = ? LIMIT 1');
$stmtAntiga->execute([$idUsuario]);
$fotoAntiga = $stmtAntiga->fetchColumn();

$stmt = $conn->prepare('UPDATE Usuario SET foto_user = ? WHERE id_user = ?');
$stmt->execute([$caminhoBanco, $idUsuario]);

if (is_string($fotoAntiga) && str_starts_with($fotoAntiga, 'public/uploads/perfis/')) {
    $arquivoAntigo = __DIR__ . '/../../../' . $fotoAntiga;
    if (is_file($arquivoAntigo) && $arquivoAntigo !== $caminhoCompleto) {
        @unlink($arquivoAntigo);
    }
}

header('Location: perfil.php', true, 303);
exit;
