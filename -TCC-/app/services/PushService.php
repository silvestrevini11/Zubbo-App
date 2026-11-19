<?php
declare(strict_types=1);

/**
 * Web Push VAPID — assinatura por navegador/dispositivo.
 * Não envia texto de mensagens, nomes ou outros dados privados ao push.
 */
final class PushService
{
    public static function publicKey(): string
    {
        return trim((string)getenv('ZUBBO_VAPID_PUBLIC_KEY'));
    }

    public static function configured(): bool
    {
        return self::publicKey() !== ''
            && trim((string)getenv('ZUBBO_VAPID_PRIVATE_KEY')) !== ''
            && trim((string)getenv('ZUBBO_VAPID_SUBJECT')) !== '';
    }

    /** Bloqueia SSRF por endpoints enviados pelo cliente. */
    public static function allowedEndpoint(string $endpoint): bool
    {
        if (strlen($endpoint) > 2048 || strlen($endpoint) < 30) return false;
        $parts = parse_url($endpoint);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || isset($parts['fragment'])) return false;
        $host = strtolower((string)($parts['host'] ?? ''));
        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) return false;
        foreach (['fcm.googleapis.com', 'android.googleapis.com', 'push.services.mozilla.com',
                  'push.apple.com', 'notify.windows.com', 'wns.windows.com'] as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) return true;
        }
        return false;
    }

    public static function validSubscription(array $data): bool
    {
        $endpoint = $data['endpoint'] ?? null;
        $keys = $data['keys'] ?? null;
        if (!is_string($endpoint) || !self::allowedEndpoint($endpoint) || !is_array($keys)) return false;
        foreach (['p256dh', 'auth'] as $name) {
            $key = $keys[$name] ?? null;
            if (!is_string($key) || strlen($key) < 12 || strlen($key) > 160 ||
                !preg_match('/^[A-Za-z0-9_-]+$/D', $key)) return false;
        }
        return true;
    }

    public static function sendToUser(PDO $db, int $id, string $title, string $body, string $url): void
    {
        if ($id <= 0 || !self::configured()) return;
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!is_file($autoload)) {
            error_log('Zubbo Web Push: dependencias Composer ausentes.');
            return;
        }
        require_once $autoload;
        if (!class_exists(\Minishlink\WebPush\WebPush::class)) {
            error_log('Zubbo Web Push: biblioteca nao instalada.');
            return;
        }
        $query = $db->prepare("SELECT p.endpoint_hash, p.dados_json
            FROM PushSubscription p INNER JOIN Usuario u ON u.id_user=p.id_user
            WHERE p.id_user = ? AND u.status_user='ativo'
            ORDER BY p.atualizado_em DESC LIMIT 30");
        $query->execute([$id]);
        $dados = $query->fetchAll(PDO::FETCH_ASSOC);
        if (!$dados) return;

        // Um URL interno controlado pelo servidor; nunca aceitar um destino de usuário.
        if (!preg_match('#^/app/views/[a-zA-Z0-9/_-]+\.php(?:\?[a-zA-Z0-9=&_-]+)?$#D', $url)) {
            $url = '/app/views/notificacoes/notificacoes.php';
        }
        $payload = json_encode([
            'title' => mb_substr($title, 0, 70),
            'body' => mb_substr($body, 0, 150),
            'url' => $url,
            'tag' => 'zubbo-' . bin2hex(random_bytes(6)),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        try {
            $webPush = new \Minishlink\WebPush\WebPush(['VAPID' => [
                'subject' => (string)getenv('ZUBBO_VAPID_SUBJECT'),
                'publicKey' => self::publicKey(),
                'privateKey' => (string)getenv('ZUBBO_VAPID_PRIVATE_KEY'),
            ]], ['TTL' => 3600, 'urgency' => 'normal', 'contentType' => 'application/json']);
            foreach ($dados as $item) {
                try {
                    $json = json_decode((string)$item['dados_json'], true, 512, JSON_THROW_ON_ERROR);
                    if (!is_array($json) || !self::validSubscription($json)) continue;
                    $subscription = \Minishlink\WebPush\Subscription::create($json);
                    $report = $webPush->sendOneNotification($subscription, $payload);
                    if ($report->isSubscriptionExpired()) {
                        $db->prepare('DELETE FROM PushSubscription WHERE endpoint_hash=? AND id_user=?')
                            ->execute([$item['endpoint_hash'], $id]);
                    } elseif (!$report->isSuccess()) {
                        error_log('Zubbo Web Push: resposta nao entregue ao push service.');
                    }
                } catch (\Throwable $e) {
                    error_log('Zubbo Web Push: falha individual ' . get_class($e));
                }
            }
        } catch (\Throwable $e) {
            error_log('Zubbo Web Push: configuracao ou transporte ' . get_class($e));
        }
    }
}
