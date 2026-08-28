<?php

session_start();

include __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: form-usuario.php');
    exit;
}

$id_user = $_SESSION['usuario']['id'];

if (!isset($_FILES['fotoPerfil'])) {
    header('Location: perfil.php');
    exit;
}

$arquivo = $_FILES['fotoPerfil'];

if ($arquivo['error'] !== UPLOAD_ERR_OK) {
    header('Location: perfil.php');
    exit;
}

$tiposPermitidos = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

$tipo = mime_content_type($arquivo['tmp_name']);

if (!isset($tiposPermitidos[$tipo])) {
    die('Tipo de imagem não permitido.');
}

$pasta = __DIR__ . '/../../../public/uploads/perfis/';

if (!is_dir($pasta)) {
    mkdir($pasta, 0755, true);
}

$extensao = $tiposPermitidos[$tipo];

$nomeArquivo = 'perfil_' . $id_user . '_' . time() . '.' . $extensao;

$caminhoCompleto = $pasta . $nomeArquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
    die('Não foi possível salvar a imagem.');
}

$caminhoBanco = 'public/uploads/perfis/' . $nomeArquivo;

$stmt = $conn->prepare("
    UPDATE Usuario
    SET foto_user = ?
    WHERE id_user = ?
");

$stmt->execute([
    $caminhoBanco,
    $id_user
]);

header('Location: perfil.php');
exit;