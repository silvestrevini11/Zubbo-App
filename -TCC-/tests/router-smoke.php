<?php
declare(strict_types=1);

// Somente leitura: verifica o roteador local mesmo sem banco configurado.
$base = rtrim((string) getenv('ZUBBO_TEST_BASE_URL'), '/');
$url = parse_url($base);
if (PHP_SAPI !== 'cli' || !in_array($url['host'] ?? '', ['localhost', '127.0.0.1'], true)
    || ($url['scheme'] ?? '') !== 'http' || !extension_loaded('curl')) {
    fwrite(STDERR, "Use PHP CLI com curl e ZUBBO_TEST_BASE_URL=http://127.0.0.1:PORTA.\n");
    exit(2);
}
$failures = 0;
$checks = 0;
function probe(string $path, int $expected, string $method = 'GET', ?string $contains = null): void
{
    global $base, $failures, $checks;
    $checks++;
    $curl = curl_init($base . '/' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15, CURLOPT_CUSTOMREQUEST => $method]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    $ok = $body !== false && $status === $expected && ($contains === null || str_contains($body, $contains));
    if (!$ok) $failures++;
    echo ($ok ? 'OK: ' : 'FALHOU: ') . "$method /$path HTTP $status (esperado $expected)\n";
}
probe('health.php', 200, 'GET', '"status":"ok"');
probe('', 302);
probe('app/views/auth/login.php', 200);
probe('app/views/painel/sugestoes-busca.php', 401);
foreach (['pesquisa/pesquisar-perfis.php', 'pesquisa/pesquisar-poles.php', 'pesquisa/pesquisar-eventos.php', 'perfil/confirmar-email.php'] as $path) {
    probe('app/views/' . $path, 302);
}
probe('public/css/style.css', 200);
probe('sw.js', 200, 'GET', 'addEventListener');
probe('public/manifest.php', 200, 'GET', '"start_url"');
// Comprovar que um 404 protege arquivos existentes, não apenas nomes fictícios.
foreach (['config/database.php', 'database/scripts/criacaotables.sql', 'database/README.md',
    'vendor/autoload.php', 'README.md', 'docs/PLANO_DEMO_02.md', 'docs/AMBIENTE_DEMO_02.md',
    'composer.json', 'composer.lock', 'tests/environment-readiness.php', 'tests/router-smoke.php',
    'router.php', 'app/views/admin/_auth.php'] as $path) {
    if (!is_file(__DIR__ . '/../' . $path)) {
        $failures++;
        echo "FALHOU: fixture interna ausente: $path\n";
    }
    probe($path, 404);
}
foreach (['.env', '.git/config', '.local/logs/private.log', 'database/archive/pre-consolidacao/README.md', '%2eenv'] as $path) {
    probe($path, 404);
}
foreach (['notificacoes/aceitar-amizade.php', 'perfil/adicionar-amigo.php', 'chats/enviar-mensagem.php'] as $path) {
    probe('app/views/' . $path, 403, 'POST');
    probe('app/views/' . $path, 405);
}
echo "$checks verificações HTTP; $failures falhas.\n";
exit($failures > 0 ? 1 : 0);
