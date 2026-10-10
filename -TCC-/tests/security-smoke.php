<?php
declare(strict_types=1);

// Testes rápidos e locais; não acessam credenciais, banco ou Railway.
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/logger.php';

function checkSecurity(bool $ok, string $message): void
{
    if (!$ok) {
        fwrite(STDERR, 'FALHOU: ' . $message . PHP_EOL);
        exit(1);
    }
    echo 'OK: ' . $message . PHP_EOL;
}

$_SESSION = [];
$t = zubbo_csrf_token();
checkSecurity((bool) preg_match('/^[a-f0-9]{64}$/', $t), 'CSRF aleatorio com 256 bits');
checkSecurity(hash_equals($t, zubbo_csrf_token()), 'CSRF permanece estavel durante a sessao');

$_SERVER['REQUEST_URI'] = '/painel?token=NAO_LOGAR';
$record = zubbo_log_encode('warning', 'auth.login_denied', [
    'email' => 'segredo@example.test',
    'password' => 'segredo-super-secreto',
    'token' => 'token-muito-privado',
    'descricao' => 'conteudo-privado',
    'user_id' => 42,
    'reason_code' => 'invalid_credentials',
]);
$data = json_decode($record, true, 512, JSON_THROW_ON_ERROR);
checkSecurity($data['event'] === 'auth.login_denied', 'evento JSON estruturado');
checkSecurity(($data['context']['user_id'] ?? null) === 42, 'contexto tecnico permitido');
checkSecurity($data['route'] === '/painel', 'rota sem query string');
foreach (['segredo@example.test', 'segredo-super-secreto', 'NAO_LOGAR', 'token-muito-privado', 'conteudo-privado'] as $secret) {
    checkSecurity(!str_contains($record, $secret), 'segredo excluido de log estruturado');
}

$perfis = (string) file_get_contents(__DIR__ . '/../app/views/pesquisa/pesquisar-perfis.php');
checkSecurity(str_contains($perfis, 'middleware/auth.php'), 'busca de perfis exige autenticacao');
checkSecurity(!str_contains($perfis, 'email_user'), 'busca de perfis nao consulta e-mails');

$admin = (string) file_get_contents(__DIR__ . '/../app/services/AdminService.php');
checkSecurity(str_contains($admin, 'WHERE id_user = ? AND ativo = 1'), 'admin vinculado por ID de usuario');
checkSecurity(!str_contains($admin, 'id_user IS NULL AND email_adm'), 'fallback antigo por e-mail removido');

echo "Testes de seguranca basicos concluidos.\n";
