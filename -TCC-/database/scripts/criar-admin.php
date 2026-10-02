<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

require_once __DIR__ . '/../../config/database.php';

$nome = trim((string) readline('Nome do administrador: '));
$email = trim((string) readline('E-mail: '));
$senha = (string) readline('Senha (mínimo 12 caracteres): ');

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 12) {
    fwrite(STDERR, "Dados inválidos.\n");
    exit(1);
}

$stmt = $conn->prepare('SELECT id_adm FROM Administrador WHERE email_adm = ? LIMIT 1');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    fwrite(STDERR, "Já existe um administrador com esse e-mail.\n");
    exit(1);
}

$hash = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conn->prepare(
    'INSERT INTO Administrador (nome_adm, email_adm, senha_adm, ativo)
     VALUES (?, ?, ?, 1)'
);
$stmt->execute([$nome, $email, $hash]);

fwrite(STDOUT, "Administrador criado com segurança.\n");
