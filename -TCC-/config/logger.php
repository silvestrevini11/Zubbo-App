<?php
declare(strict_types=1);

/**
 * Logs estruturados para stdout/stderr do Railway via error_log.
 * APENAS campos da lista abaixo são aceitos; nunca registrar mensagens,
 * tokens, credenciais, e-mail, telefone, conteúdo de denúncias ou cookies.
 */
function zubbo_log_encode(string $level, string $event, array $context = []): string
{
    $allowedLevels = ['debug', 'info', 'warning', 'error', 'critical'];
    if (!in_array($level, $allowedLevels, true)) {
        $level = 'info';
    }
    $event = preg_match('/^[a-z][a-z0-9_.-]{0,79}$/', $event)
        ? $event : 'application.event';

    $allowed = [
        'user_id', 'admin_id', 'event_id', 'group_id', 'conversation_id',
        'report_id', 'local_id', 'http_status', 'duration_ms',
        'count', 'action', 'reason_code', 'result', 'entity_type'
    ];
    $safeContext = [];
    foreach ($allowed as $key) {
        if (!array_key_exists($key, $context)) {
            continue;
        }
        $value = $context[$key];
        if (is_int($value) || is_float($value) || is_bool($value)) {
            $safeContext[$key] = $value;
        } elseif (is_string($value) && preg_match('/^[a-zA-Z0-9_.-]{1,50}$/D', $value)) {
            $safeContext[$key] = $value;
        }
    }

    if (empty($_SERVER['ZUBBO_REQUEST_ID'])) {
        $_SERVER['ZUBBO_REQUEST_ID'] = bin2hex(random_bytes(8));
    }

    $route = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $record = [
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'level' => $level,
        'event' => $event,
        'request_id' => (string) $_SERVER['ZUBBO_REQUEST_ID'],
        'environment' => preg_match('/^[a-z0-9_-]{1,24}$/i', (string) getenv('ZUBBO_ENV'))
            ? getenv('ZUBBO_ENV') : 'unknown',
        'route' => is_string($route) ? substr($route, 0, 160) : '',
        'context' => $safeContext,
    ];
    return (string) json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

function zubbo_log(string $level, string $event, array $context = []): void
{
    error_log(zubbo_log_encode($level, $event, $context));
}
