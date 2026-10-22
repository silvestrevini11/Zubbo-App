<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/logger.php';

if (PHP_SAPI !== 'cli') {
    zubbo_start_session();
    zubbo_reject_cross_site_post();
}

$host = getenv('ZUBBO_DB_HOST') ?: 'localhost';
$user = getenv('ZUBBO_DB_USER') ?: 'root';
$password = getenv('ZUBBO_DB_PASSWORD') ?: '';
$port = (int) (getenv('ZUBBO_DB_PORT') ?: 3306);
$database = getenv('ZUBBO_DB_NAME') ?: 'app_zubbo';

try {
    $conn = new PDO(
        'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database . ';charset=utf8mb4',
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    zubbo_log('critical', 'db.connection_failed', ['reason_code' => 'pdo_unavailable']);
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}

if (PHP_SAPI !== 'cli' && !empty($_SESSION['usuario']['id'])) {
    $stmtStatus = $conn->prepare(
        'SELECT nome_user, email_user, status_user, senha_user FROM Usuario WHERE id_user = ? LIMIT 1'
    );
    $stmtStatus->execute([(int) $_SESSION['usuario']['id']]);
    $usuarioAtual = $stmtStatus->fetch(PDO::FETCH_ASSOC);

    $fingerprintAtual = $usuarioAtual
        ? hash('sha256', (string) $usuarioAtual['senha_user'])
        : '';
    $fingerprintSessao = (string) ($_SESSION['credential_fingerprint'] ?? '');
    if (!$usuarioAtual || $usuarioAtual['status_user'] !== 'ativo'
        || $fingerprintSessao === ''
        || !hash_equals($fingerprintSessao, $fingerprintAtual)) {
        zubbo_log('warning', 'auth.session_revoked', [
            'user_id' => (int) $_SESSION['usuario']['id'],
            'reason_code' => 'inactive_or_credentials_changed',
        ]);
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ' . zubbo_url('/app/views/auth/login.php?conta=inativa'));
        exit;
    }

    $_SESSION['usuario']['nome'] = $usuarioAtual['nome_user'];
    $_SESSION['usuario']['email'] = $usuarioAtual['email_user'];
}
