<?php
require_once __DIR__ . '/security.php';
zubbo_security_headers();
zubbo_reject_cross_site_post();

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

$scriptAtual = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if (
    session_status() === PHP_SESSION_ACTIVE
    && !empty($_SESSION['usuario']['id'])
    && strpos($scriptAtual, '/admin/') === false
) {
    $stmtStatus = $conn->prepare('SELECT status_user FROM Usuario WHERE id_user = ? LIMIT 1');
    $stmtStatus->execute([(int) $_SESSION['usuario']['id']]);
    $statusAtual = $stmtStatus->fetchColumn();

    if ($statusAtual !== 'ativo') {
        unset($_SESSION['usuario']);
        session_regenerate_id(true);
        header('Location: /-TCC-/app/views/auth/login.php?conta=inativa');
        exit;
    }
}
