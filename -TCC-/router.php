<?php
declare(strict_types=1);

// Roteador do servidor PHP utilizado no Railway.
// Somente páginas públicas da aplicação e arquivos estáticos permitidos são expostos.
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$path = rawurldecode(is_string($path) ? $path : '/');

if ($path === '' || strpos($path, "\0") !== false || str_contains($path, '..') || str_contains($path, '\\')) {
    http_response_code(404);
    exit('Página não encontrada.');
}

if ($path === '/favicon.ico') {
    header('Location: /public/imagem/LogooZ.png', true, 302);
    exit;
}

$publicas = ['/', '/index.php', '/health.php', '/public/index.php', '/public/reset-password.php'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$estaticas = ['css', 'js', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'ttf', 'woff', 'woff2'];
$appPhp = str_starts_with($path, '/app/views/')
    && $ext === 'php'
    && !str_contains($path, '/includes/')
    && !str_starts_with(basename($path), '_');
$publicStatic = str_starts_with($path, '/public/') && in_array($ext, $estaticas, true);

if (!in_array($path, $publicas, true) && !$appPhp && !$publicStatic) {
    http_response_code(404);
    exit('Página não encontrada.');
}

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

$arquivo = realpath(__DIR__ . $path);
$raiz = realpath(__DIR__);
if ($arquivo === false || $raiz === false
    || !str_starts_with($arquivo, $raiz . DIRECTORY_SEPARATOR)
    || !is_file($arquivo)) {
    http_response_code(404);
    exit('Página não encontrada.');
}

return false;
