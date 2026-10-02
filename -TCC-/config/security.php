<?php
declare(strict_types=1);

function zubbo_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function zubbo_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), payment=()');
    header("Content-Security-Policy: default-src 'self' https: data: blob:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://api.mapbox.com; style-src 'self' 'unsafe-inline' https://unpkg.com https://api.mapbox.com https://fonts.googleapis.com https://googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: blob: https:; connect-src 'self' https://api.mapbox.com https://events.mapbox.com https://*.tiles.mapbox.com; worker-src 'self' blob:");

    if (zubbo_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function zubbo_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        zubbo_security_headers();
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

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
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function zubbo_csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(zubbo_csrf_token(), ENT_QUOTES, 'UTF-8') .
        '">';
}

function zubbo_require_csrf(string $field = 'csrf_token'): void
{
    $recebido = $_POST[$field] ?? '';
    $sessao = $_SESSION['csrf_token'] ?? '';

    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST'
        || !is_string($recebido)
        || !is_string($sessao)
        || $sessao === ''
        || !hash_equals($sessao, $recebido)
    ) {
        http_response_code(403);
        exit('Solicitação inválida.');
    }
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

    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '') {
        $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
        $host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));

        if ($originHost === '' || $host === '' || !hash_equals($host, $originHost)) {
            http_response_code(403);
            exit('Origem da solicitação inválida.');
        }
    }
}

function zubbo_rate_limit_exceeded(string $scope, int $max = 5, int $window = 900): bool
{
    $agora = time();
    $chave = 'rate_' . $scope;
    $tentativas = $_SESSION[$chave] ?? [];

    if (!is_array($tentativas)) {
        $tentativas = [];
    }

    $tentativas = array_values(array_filter(
        $tentativas,
        static fn($t): bool => is_numeric($t) && (int) $t > ($agora - $window)
    ));

    $_SESSION[$chave] = $tentativas;
    return count($tentativas) >= $max;
}

function zubbo_rate_limit_hit(string $scope): void
{
    $chave = 'rate_' . $scope;
    if (!isset($_SESSION[$chave]) || !is_array($_SESSION[$chave])) {
        $_SESSION[$chave] = [];
    }
    $_SESSION[$chave][] = time();
}

function zubbo_rate_limit_reset(string $scope): void
{
    unset($_SESSION['rate_' . $scope]);
}
