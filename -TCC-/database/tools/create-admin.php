<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/AdminService.php';

fwrite(STDOUT, "E-mail da conta que será administradora: ");
$email = strtolower(trim((string) fgets(STDIN)));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "E-mail inválido.\n");
    exit(1);
}

$stmt = $conn->prepare(
    "SELECT id_user, nome_user, email_user
     FROM Usuario
     WHERE email_user = ? AND status_user = 'ativo'
     LIMIT 1"
);
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    fwrite(STDERR, "Cadastre e ative essa conta no Zubbo antes de promovê-la.\n");
    exit(1);
}

$idAdmin = AdminService::conceder($conn, $usuario);
fwrite(STDOUT, "Administrador #{$idAdmin} configurado. Use o login normal do Zubbo.\n");
