<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

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
    error_log('Falha de conexão com o banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}

if (PHP_SAPI !== 'cli' && !empty($_SESSION['usuario']['id'])) {
    $stmtStatus = $conn->prepare(
        'SELECT nome_user, email_user, status_user FROM Usuario WHERE id_user = ? LIMIT 1'
    );
    $stmtStatus->execute([(int) $_SESSION['usuario']['id']]);
    $usuarioAtual = $stmtStatus->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioAtual || $usuarioAtual['status_user'] !== 'ativo') {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ' . zubbo_url('/app/views/auth/login.php?conta=inativa'));
        exit;
    }

    $_SESSION['usuario']['nome'] = $usuarioAtual['nome_user'];
    $_SESSION['usuario']['email'] = $usuarioAtual['email_user'];
}
