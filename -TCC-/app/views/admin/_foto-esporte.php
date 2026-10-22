<?php
declare(strict_types=1);
function admin_salvar_foto_esporte(array $arq): ?string
{
    if (($arq['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($arq['error'] ?? -1) !== UPLOAD_ERR_OK ||
        ($arq['size'] ?? 0) > 4*1024*1024 ||
        ($arq['size'] ?? 0) < 1 ||
        !is_uploaded_file($arq['tmp_name'] ?? '')) throw new RuntimeException('Imagem inválida.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($arq['tmp_name']);
    $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
    if (!isset($ext[$mime]) || @getimagesize($arq['tmp_name'])===false) throw new RuntimeException('Use JPG, PNG ou WEBP.');
    $dir=__DIR__.'/../../../public/uploads/esportes';
    if (!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)) throw new RuntimeException('Pasta de imagens indisponível.');
    $nome=bin2hex(random_bytes(16)).'.'.$ext[$mime];
    if (!move_uploaded_file($arq['tmp_name'],$dir.'/'.$nome)) throw new RuntimeException('Não foi possível salvar a imagem.');
    return 'public/uploads/esportes/'.$nome;
}
