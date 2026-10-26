<?php
declare(strict_types=1);

// Diagnóstico CLI: não carrega config/database.php, não envia e-mail/push e não altera o banco.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$options = getopt('', ['skip-db', 'expected-base-url:']);
$failures = 0;
function result(bool $ok, string $name): void
{
    global $failures;
    if (!$ok) $failures++;
    echo ($ok ? 'OK: ' : 'FALHOU: ') . $name . PHP_EOL;
}
function env(string $name): string
{
    return trim((string) getenv($name));
}
function httpsOrigin(string $value): bool
{
    $parts = parse_url($value);
    return filter_var($value, FILTER_VALIDATE_URL) !== false && is_array($parts)
        && ($parts['scheme'] ?? '') === 'https' && !empty($parts['host'])
        && !isset($parts['user']) && !isset($parts['pass'])
        && !isset($parts['port']) && !isset($parts['query']) && !isset($parts['fragment'])
        && in_array($parts['path'] ?? '', ['', '/'], true);
}
result(PHP_VERSION_ID >= 80400 && PHP_VERSION_ID < 80500, 'PHP 8.4');
foreach (['pdo_mysql', 'curl', 'json', 'mbstring', 'openssl', 'ctype', 'fileinfo', 'filter', 'hash'] as $extension) {
    result(extension_loaded($extension), 'ext-' . $extension);
}
result(is_file(__DIR__ . '/../vendor/autoload.php'), 'Composer autoload presente');
result(env('ZUBBO_ENV') === 'demo', 'ZUBBO_ENV');
result(filter_var(env('ZUBBO_DEMO_MODE'), FILTER_VALIDATE_BOOLEAN), 'ZUBBO_DEMO_MODE');
result(env('ZUBBO_BASE_PATH') === '/', 'ZUBBO_BASE_PATH');
$base = env('ZUBBO_BASE_URL');
result(httpsOrigin($base), 'ZUBBO_BASE_URL (origem HTTPS)');
if (array_key_exists('expected-base-url', $options)) {
    $expected = (string) $options['expected-base-url'];
    result(httpsOrigin($expected) && rtrim($base, '/') === rtrim($expected, '/'),
        'ZUBBO_BASE_URL corresponde ao domínio confirmado');
}
foreach (['ZUBBO_TEST_ALLOW_SCHEMA', 'ZUBBO_TEST_BASE_URL'] as $name) {
    result(env($name) === '', $name . ' ausente');
}
result(env('PORT') === '' || (ctype_digit(env('PORT')) && (int) env('PORT') > 0
    && (int) env('PORT') <= 65535), 'PORT (atribuída pela plataforma)');
$dbValid = true;
foreach (['ZUBBO_DB_HOST', 'ZUBBO_DB_PORT', 'ZUBBO_DB_NAME', 'ZUBBO_DB_USER', 'ZUBBO_DB_PASSWORD'] as $name) {
    $present = env($name) !== '';
    result($present, $name);
    $dbValid = $dbValid && $present;
}
$dbShape = preg_match('/^[A-Za-z0-9._:-]+$/D', env('ZUBBO_DB_HOST')) === 1
    && !in_array(strtolower(env('ZUBBO_DB_HOST')), ['localhost', '127.0.0.1', '::1'], true)
    && preg_match('/^[A-Za-z0-9_]+$/D', env('ZUBBO_DB_NAME')) === 1
    && ctype_digit(env('ZUBBO_DB_PORT')) && (int) env('ZUBBO_DB_PORT') > 0
    && (int) env('ZUBBO_DB_PORT') <= 65535;
result($dbShape, 'ZUBBO_DB_* formato e destino remoto');
$dbPrivate = preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\.railway\.internal$/Di', env('ZUBBO_DB_HOST')) === 1;
result($dbPrivate, 'ZUBBO_DB_HOST usa rede privada Railway');
$provider = env('ZUBBO_MAIL_PROVIDER');
$resend = env('ZUBBO_RESEND_API_KEY');
result(in_array($provider, ['resend', 'smtp'], true) || ($provider === '' && $resend !== ''),
    'ZUBBO_MAIL_PROVIDER');
$from = env('ZUBBO_MAIL_FROM');
if (preg_match('/^\s*.*?\s*<([^<>]+)>\s*$/', $from, $match)) $from = trim($match[1]);
result(filter_var($from, FILTER_VALIDATE_EMAIL) !== false, 'ZUBBO_MAIL_FROM');
if ($provider === 'smtp') {
    foreach (['ZUBBO_SMTP_HOST', 'ZUBBO_SMTP_USER', 'ZUBBO_SMTP_PASSWORD'] as $name) {
        result(env($name) !== '', $name);
    }
    $smtpPort = env('ZUBBO_SMTP_PORT') ?: '587';
    result(ctype_digit($smtpPort) && (int) $smtpPort > 0 && (int) $smtpPort <= 65535, 'ZUBBO_SMTP_PORT');
    result(in_array(env('ZUBBO_SMTP_ENCRYPTION'), ['', 'auto', 'starttls', 'smtps'], true),
        'ZUBBO_SMTP_ENCRYPTION (TLS)');
} else {
    result($resend !== '', 'ZUBBO_RESEND_API_KEY');
}
$subject = env('ZUBBO_VAPID_SUBJECT');
result((str_starts_with($subject, 'mailto:') && filter_var(substr($subject, 7), FILTER_VALIDATE_EMAIL) !== false)
    || httpsOrigin($subject), 'ZUBBO_VAPID_SUBJECT');
$decode = static function (string $value): string|false {
    return preg_match('/^[A-Za-z0-9_-]+$/D', $value) === 1
        ? base64_decode(strtr($value, '-_', '+/'), true) : false;
};
$public = $decode(env('ZUBBO_VAPID_PUBLIC_KEY'));
$private = $decode(env('ZUBBO_VAPID_PRIVATE_KEY'));
result(is_string($public) && strlen($public) === 65 && $public[0] === "\x04", 'ZUBBO_VAPID_PUBLIC_KEY');
result(is_string($private) && strlen($private) === 32, 'ZUBBO_VAPID_PRIVATE_KEY');
$pairValid = false;
if (extension_loaded('openssl') && is_string($private) && strlen($private) === 32 && is_string($public)) {
    // SEC1 ECPrivateKey: OpenSSL deriva as coordenadas públicas do escalar privado.
    $der = hex2bin('30310201010420') . $private . hex2bin('a00a06082a8648ce3d030107');
    $key = @openssl_pkey_get_private("-----BEGIN EC PRIVATE KEY-----\n"
        . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n");
    $details = $key === false ? false : openssl_pkey_get_details($key);
    if (is_array($details) && isset($details['ec']['x'], $details['ec']['y'])) {
        $derived = "\x04" . str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)
            . str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $pairValid = hash_equals($derived, $public);
    }
    while (openssl_error_string() !== false) { /* Não imprimir dados de OpenSSL. */ }
}
result($pairValid, 'ZUBBO_VAPID_* par criptográfico');
result(preg_match('/^pk\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/D', env('ZUBBO_MAPBOX_PUBLIC_TOKEN')) === 1,
    'ZUBBO_MAPBOX_PUBLIC_TOKEN');
foreach (['ZUBBO_MAPBOX_STYLE_LIGHT', 'ZUBBO_MAPBOX_STYLE_DARK'] as $name) {
    result(env($name) === '' || preg_match('~^mapbox://styles/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+$~D', env($name)) === 1, $name);
}
if (array_key_exists('skip-db', $options)) {
    echo 'PENDENTE: conexão, identidade e versão do banco omitidas por --skip-db; não aprova publicação.' . PHP_EOL;
} else {
    $connected = false;
    $identityMatches = false;
    $mysql84 = false;
    if ($dbValid && $dbShape && $dbPrivate && extension_loaded('pdo_mysql')) {
        try {
            $db = new PDO('mysql:host=' . env('ZUBBO_DB_HOST') . ';port=' . env('ZUBBO_DB_PORT')
                . ';dbname=' . env('ZUBBO_DB_NAME') . ';charset=utf8mb4', env('ZUBBO_DB_USER'),
                (string) getenv('ZUBBO_DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5, PDO::ATTR_EMULATE_PREPARES => false]);
            $connected = (int) $db->query('SELECT 1')->fetchColumn() === 1;
            $identityMatches = $db->query('SELECT DATABASE()')->fetchColumn() === env('ZUBBO_DB_NAME');
            $version = (string) $db->query('SELECT VERSION()')->fetchColumn();
            $mysql84 = preg_match('/^8\.4\.[0-9]+(?:-|$)/D', $version) === 1
                && stripos($version, 'mariadb') === false;
        } catch (Throwable) { /* Não imprimir DSN, credenciais nem detalhes do driver. */ }
    }
    result($connected, 'ZUBBO_DB_* conexão SELECT 1');
    result($identityMatches, 'Banco conectado corresponde a ZUBBO_DB_NAME');
    result($mysql84, 'Servidor MySQL 8.4');
}

echo 'PENDENTE: conferir serviço/volume, credenciais vigentes, entrega de e-mail/push e mapa na nova origem.' . PHP_EOL;
echo 'Diagnóstico somente leitura; ' . $failures . ' falha(s). Credenciais e valores omitidos.' . PHP_EOL;
exit($failures === 0 ? 0 : 1);
