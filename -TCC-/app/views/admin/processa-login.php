<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    $_SESSION['admin_erro_login'] = 'Informe e-mail e senha válidos.';
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare('SELECT id_adm, nome_adm, email_adm, senha_adm, ativo FROM Administrador WHERE email_adm = ? LIMIT 1');
$stmt->execute([$email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$senhaValida = false;
if ($admin) {
    $senhaBanco = (string) $admin['senha_adm'];
    $infoHash = password_get_info($senhaBanco);

    if (!empty($infoHash['algo'])) {
        $senhaValida = password_verify($senha, $senhaBanco);
    } else {
        // Compatibilidade com o banco antigo do projeto, que salvava a senha do admin em texto puro.
        $senhaValida = hash_equals($senhaBanco, $senha);
        if ($senhaValida) {
            $novoHash = password_hash($senha, PASSWORD_DEFAULT);
            $up = $conn->prepare('UPDATE Administrador SET senha_adm = ? WHERE id_adm = ?');
            $up->execute([$novoHash, $admin['id_adm']]);
        }
    }
}

if (!$admin || !$senhaValida) {
    $_SESSION['admin_erro_login'] = 'E-mail ou senha incorretos.';
    header('Location: login.php');
    exit;
}

if (!(bool) $admin['ativo']) {
    $_SESSION['admin_erro_login'] = 'Esta conta administrativa está desativada.';
    header('Location: login.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['admin'] = [
    'id' => (int) $admin['id_adm'],
    'nome' => $admin['nome_adm'],
    'email' => $admin['email_adm'],
];
$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

header('Location: painel.php');
exit;
