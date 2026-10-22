<?php
declare(strict_types=1);

/** Salva imagem válida do local, ou retorna NULL caso nenhum arquivo seja enviado. */
function admin_salvar_foto_local(array $arquivo): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($arquivo['error'] ?? -1) !== UPLOAD_ERR_OK
        || ($arquivo['size'] ?? 0) < 1
        || $arquivo['size'] > 4 * 1024 * 1024
        || !is_uploaded_file($arquivo['tmp_name'] ?? '')) {
        throw new RuntimeException('Arquivo inválido ou maior que 4 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($ext[$mime]) || @getimagesize($arquivo['tmp_name']) === false) {
        throw new RuntimeException('Envie apenas JPG, PNG ou WEBP.');
    }
    $pasta = __DIR__ . '/../../../public/uploads/locais';
    if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) {
        throw new RuntimeException('Diretório de fotos indisponível.');
    }
    $nome = bin2hex(random_bytes(16)) . '.' . $ext[$mime];
    if (!move_uploaded_file($arquivo['tmp_name'], $pasta . '/' . $nome)) {
        throw new RuntimeException('Falha ao salvar foto.');
    }
    return 'public/uploads/locais/' . $nome;
}
