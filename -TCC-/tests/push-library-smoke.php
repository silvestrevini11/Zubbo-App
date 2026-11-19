<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Composer não instalou dependências do Web Push.\n");
    exit(1);
}
require_once $autoload;
if (!class_exists(\Minishlink\WebPush\WebPush::class)
    || !class_exists(\Minishlink\WebPush\VAPID::class)) {
    fwrite(STDERR, "Biblioteca Web Push ou VAPID indisponível.\n");
    exit(1);
}
$chaves = \Minishlink\WebPush\VAPID::createVapidKeys();
if (empty($chaves['publicKey']) || empty($chaves['privateKey'])) {
    fwrite(STDERR, "Falha ao gerar par VAPID.\n");
    exit(1);
}
echo "Biblioteca Web Push carregada e par VAPID válido.\n";
