<?php
declare(strict_types=1);

function zubbo_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function zubbo_base_path(): string
{
    $configured = trim((string) getenv('ZUBBO_BASE_PATH'));
    if ($configured !== '') {
        return '/' . trim($configured, '/');
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $marker = '/-TCC-/';
    $pos = strpos($script, $marker);

    if ($pos !== false) {
        return rtrim(substr($script, 0, $pos + strlen('/-TCC-')), '/');
    }

    if (str_ends_with($script, '/-TCC-')) {
        return rtrim($script, '/');
    }

    return '/-TCC-';
}

function zubbo_url(string $path = ''): string
{
    return rtrim(zubbo_base_path(), '/') . '/' . ltrim($path, '/');
}

function zubbo_absolute_url(string $path = ''): string
{
    $configured = trim((string) getenv('ZUBBO_BASE_URL'));
    if ($configured !== '') {
        return rtrim($configured, '/') . '/' . ltrim($path, '/');
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        throw new RuntimeException('ZUBBO_BASE_URL não configurada.');
    }

    $scheme = zubbo_is_https() ? 'https' : 'http';
    return $scheme . '://' . $host . zubbo_url($path);
}

function zubbo_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=()');
    header("Content-Security-Policy: default-src 'self' https: data: blob:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://api.mapbox.com; style-src 'self' 'unsafe-inline' https://unpkg.com https://api.mapbox.com https://fonts.googleapis.com https://googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: blob: https:; connect-src 'self' https://api.mapbox.com https://events.mapbox.com https://*.tiles.mapbox.com; worker-src 'self' blob:");

    if (zubbo_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function zubbo_start_session(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        zubbo_security_headers();
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => zubbo_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
    zubbo_security_headers();
}

function zubbo_csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function zubbo_csrf_input(string $field = 'csrf_token'): string
{
    return '<input type="hidden" name="' .
        htmlspecialchars($field, ENT_QUOTES, 'UTF-8') .
        '" value="' .
        htmlspecialchars(zubbo_csrf_token(), ENT_QUOTES, 'UTF-8') .
        '">';
}

function zubbo_require_csrf(string $field = 'csrf_token'): void
{
    $received = $_POST[$field] ?? '';
    $session = $_SESSION['csrf_token'] ?? '';

    if (
        ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
        || !is_string($received)
        || !is_string($session)
        || $session === ''
        || !hash_equals($session, $received)
    ) {
        http_response_code(403);
        exit('Solicitação inválida.');
    }
}

function zubbo_request_origin(): array
{
    $scheme = zubbo_is_https() ? 'https' : 'http';
    $hostHeader = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $parts = parse_url($scheme . '://' . $hostHeader);

    $host = strtolower((string) ($parts['host'] ?? ''));
    $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

    return [$scheme, $host, $port];
}

function zubbo_reject_cross_site_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
    if ($fetchSite === 'cross-site') {
        http_response_code(403);
        exit('Solicitação entre sites bloqueada.');
    }

    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') {
        return;
    }

    $parts = parse_url($origin);
    if (!is_array($parts)) {
        http_response_code(403);
        exit('Origem da solicitação inválida.');
    }

    $originScheme = strtolower((string) ($parts['scheme'] ?? ''));
    $originHost = strtolower((string) ($parts['host'] ?? ''));
    $originPort = isset($parts['port']) ? (int) $parts['port'] : ($originScheme === 'https' ? 443 : 80);

    [$requestScheme, $requestHost, $requestPort] = zubbo_request_origin();

    if (
        $originScheme === ''
        || $originHost === ''
        || $requestHost === ''
        || !hash_equals($requestScheme, $originScheme)
        || !hash_equals($requestHost, $originHost)
        || $requestPort !== $originPort
    ) {
        http_response_code(403);
        exit('Origem da solicitação inválida.');
    }
}

function zubbo_client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function zubbo_rate_limit_file(string $scope, string $identity = ''): string
{
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'zubbo-rate-limits';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível inicializar o limitador de tentativas.');
    }

    $key = hash('sha256', $scope . '|' . strtolower(trim($identity)) . '|' . zubbo_client_ip());
    return $dir . DIRECTORY_SEPARATOR . $key . '.json';
}

function zubbo_rate_limit_mutate(string $scope, int $window, string $identity, bool $add): array
{
    $now = time();
    $file = zubbo_rate_limit_file($scope, $identity);
    $handle = fopen($file, 'c+');

    if ($handle === false) {
        throw new RuntimeException('Não foi possível acessar o limitador de tentativas.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Não foi possível bloquear o limitador de tentativas.');
        }

        rewind($handle);
        $raw = stream_get_contents($handle);
        $attempts = $raw ? json_decode($raw, true) : [];

        if (!is_array($attempts)) {
            $attempts = [];
        }

        $attempts = array_values(array_filter(
            $attempts,
            static fn($value): bool => is_int($value) && $value > ($now - $window)
        ));

        if ($add) {
            $attempts[] = $now;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($attempts, JSON_UNESCAPED_SLASHES));
        fflush($handle);
        flock($handle, LOCK_UN);

        return $attempts;
    } finally {
        fclose($handle);
    }
}

function zubbo_rate_limit_exceeded(
    string $scope,
    int $max = 5,
    int $window = 900,
    string $identity = ''
): bool {
    try {
        return count(zubbo_rate_limit_mutate($scope, $window, $identity, false)) >= $max;
    } catch (Throwable $e) {
        error_log('Rate limit indisponível: ' . $e->getMessage());
        return false;
    }
}

function zubbo_rate_limit_hit(
    string $scope,
    int $window = 900,
    string $identity = ''
): void {
    try {
        zubbo_rate_limit_mutate($scope, $window, $identity, true);
    } catch (Throwable $e) {
        error_log('Rate limit indisponível: ' . $e->getMessage());
    }
}

function zubbo_rate_limit_reset(string $scope, string $identity = ''): void
{
    try {
        $file = zubbo_rate_limit_file($scope, $identity);
        if (is_file($file)) {
            @unlink($file);
        }
    } catch (Throwable $e) {
        error_log('Falha ao limpar rate limit: ' . $e->getMessage());
    }
}
