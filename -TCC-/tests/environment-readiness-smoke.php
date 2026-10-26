<?php
declare(strict_types=1);

// Subprocesses receive only synthetic configuration and always skip the database.
// Public P-256 generator point / scalar 1: a test fixture, NEVER a deployment key.
$encode = static fn(string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
$baseEnvironment = getenv();
foreach (array_keys($baseEnvironment) as $name) {
    if (str_starts_with($name, 'ZUBBO_')) unset($baseEnvironment[$name]);
}
$fixture = [
    'ZUBBO_ENV' => 'demo', 'ZUBBO_DEMO_MODE' => 'true', 'ZUBBO_BASE_PATH' => '/',
    'ZUBBO_BASE_URL' => 'https://synthetic-demo.up.railway.app',
    'ZUBBO_DB_HOST' => 'synthetic-db.railway.internal', 'ZUBBO_DB_PORT' => '3306',
    'ZUBBO_DB_NAME' => 'synthetic_demo', 'ZUBBO_DB_USER' => 'synthetic_user',
    'ZUBBO_DB_PASSWORD' => 'synthetic-db-password',
    'ZUBBO_MAIL_PROVIDER' => 'resend', 'ZUBBO_RESEND_API_KEY' => 'synthetic-mail-key',
    'ZUBBO_MAIL_FROM' => 'fixture@localhost.test', 'ZUBBO_VAPID_SUBJECT' => 'mailto:fixture@localhost.test',
    'ZUBBO_VAPID_PRIVATE_KEY' => $encode(str_repeat("\0", 31) . "\1"),
    'ZUBBO_VAPID_PUBLIC_KEY' => $encode(hex2bin('046b17d1f2e12c4247f8bce6e563a440f277037d812deb33a0f4a13945d898c2964fe342e2fe1a7f9b8ee7eb4a7c0f9e162bce33576b315ececbb6406837bf51f5')),
    'ZUBBO_MAPBOX_PUBLIC_TOKEN' => 'pk.synthetic.signature',
];
$scenarios = [
    ['configuração válida', [], 0],
    ['banco público recusado', ['ZUBBO_DB_HOST' => 'synthetic-db.example.test'], 1],
    ['autorização destrutiva recusada', ['ZUBBO_TEST_ALLOW_SCHEMA' => '1'], 1],
    ['chaves VAPID incompatíveis', ['ZUBBO_VAPID_PRIVATE_KEY' => $encode(str_repeat("\0", 31) . "\2")], 1],
    ['origem divergente recusada', ['ZUBBO_BASE_URL' => 'https://different-demo.up.railway.app'], 1],
];
$failures = 0;
foreach ($scenarios as [$label, $changes, $expected]) {
    $environment = array_merge($baseEnvironment, $fixture, $changes);
    $process = proc_open([PHP_BINARY, __DIR__ . '/environment-readiness.php', '--skip-db',
        '--expected-base-url=' . $fixture['ZUBBO_BASE_URL']],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
    if (!is_resource($process)) throw new RuntimeException('Subprocesso indisponível.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $status = proc_close($process);
    $safe = true;
    foreach (['ZUBBO_DB_PASSWORD', 'ZUBBO_RESEND_API_KEY', 'ZUBBO_VAPID_PRIVATE_KEY', 'ZUBBO_VAPID_PUBLIC_KEY'] as $name) {
        if (str_contains($output, $environment[$name])) $safe = false;
    }
    $ok = $status === $expected && $safe && str_contains($output, 'não aprova publicação');
    if (!$ok) $failures++;
    echo ($ok ? 'OK: ' : 'FALHOU: ') . "$label (exit $status; esperado $expected; saída sem segredos=" . ($safe ? 'sim' : 'não') . ")\n";
}
echo count($scenarios) . " cenários sintéticos; $failures falhas; nenhuma conexão de rede.\n";
exit($failures ? 1 : 0);
