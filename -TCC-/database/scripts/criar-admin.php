<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

require_once __DIR__ . '/../../config/database.php';

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
    fwrite(STDERR, "Essa conta ainda não existe em Usuario. Cadastre-a primeiro pelo Zubbo.\n");
    exit(1);
}

if ($usuario['status_user'] !== 'ativo') {
    fwrite(STDERR, "A conta precisa estar ativa para receber permissão administrativa.\n");
    exit(1);
}

$stmt = $conn->prepare(
    'SELECT id_adm, ativo
     FROM Administrador
     WHERE email_adm = ?
     LIMIT 1'
);
$stmt->execute([$usuario['email_user']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    $conn->prepare(
        'UPDATE Administrador
         SET nome_adm = ?, ativo = 1
         WHERE id_adm = ?'
    )->execute([$usuario['nome_user'], $admin['id_adm']]);

    fwrite(STDOUT, "Permissão administrativa ativada para essa conta.\n");
    exit;
}

$senhaLegadaInutilizavel = password_hash(
    bin2hex(random_bytes(32)),
    PASSWORD_DEFAULT
);

$stmt = $conn->prepare(
    'INSERT INTO Administrador
        (nome_adm, email_adm, senha_adm, ativo)
     VALUES (?, ?, ?, 1)'
);
$stmt->execute([
    $usuario['nome_user'],
    $usuario['email_user'],
    $senhaLegadaInutilizavel,
]);

fwrite(
    STDOUT,
    "Conta promovida a administrador. Use o login comum do Zubbo com a senha normal desse usuário.\n"
);
