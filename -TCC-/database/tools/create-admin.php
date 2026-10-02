<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/AdminService.php';

$email = trim((string) readline('E-mail da conta de usuário que será administradora: '));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "E-mail inválido.\n");
    exit(1);
}

$stmt = $conn->prepare(
    'SELECT id_user, nome_user, email_user, status_user
     FROM Usuario
     WHERE email_user = ?
     LIMIT 1'
);
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    fwrite(STDERR, "Essa conta ainda não existe. Cadastre-a primeiro pelo Zubbo.\n");
    exit(1);
}

if ($usuario['status_user'] !== 'ativo') {
    fwrite(STDERR, "A conta precisa estar ativa para receber permissão administrativa.\n");
    exit(1);
}

AdminService::conceder($conn, $usuario);

fwrite(
    STDOUT,
    "Permissão administrativa ativada. Use o login comum do Zubbo com essa conta.\n"
);
