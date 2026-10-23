<?php
declare(strict_types=1);

// Somente terminal: jamais exponha chaves privadas em uma rota HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$loader = __DIR__ . '/../vendor/autoload.php';
if (!is_file($loader)) {
    fwrite(STDERR, "Execute composer install dentro de -TCC-.\n");
    exit(1);
}
require_once $loader;
$keys = \Minishlink\WebPush\VAPID::createVapidKeys();
fwrite(STDOUT, "ZUBBO_VAPID_PUBLIC_KEY=" . $keys['publicKey'] . PHP_EOL);
fwrite(STDOUT, "ZUBBO_VAPID_PRIVATE_KEY=" . $keys['privateKey'] . PHP_EOL);
fwrite(STDERR, "Atenção: copie as chaves para variáveis de ambiente. NUNCA as envie ao GitHub.\n");
