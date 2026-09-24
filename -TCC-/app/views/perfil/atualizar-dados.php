<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil-editar.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

function voltarComErro(string $erro): void
{
    header('Location: perfil-editar.php?erro=' . rawurlencode($erro));
    exit;
}

function voltarComSucesso(string $sucesso): void
{
    header('Location: perfil-editar.php?sucesso=' . rawurlencode($sucesso));
    exit;
}

$tokenRecebido = $_POST['csrf_token'] ?? '';
$tokenSessao = $_SESSION['csrf_token'] ?? '';

if ($tokenSessao === '' || $tokenRecebido === '' || !hash_equals($tokenSessao, $tokenRecebido)) {
    voltarComErro('csrf');
}

$id_user = (int) $_SESSION['usuario']['id'];
$acao = $_POST['acao'] ?? '';

try {
    switch ($acao) {
        case 'nome':
            $nome = trim($_POST['nome'] ?? '');
            $tamanho = function_exists('mb_strlen') ? mb_strlen($nome, 'UTF-8') : strlen($nome);

            if ($tamanho < 3 || $tamanho > 50) {
                voltarComErro('nome');
            }

            $stmt = $conn->prepare('UPDATE Usuario SET nome_user = ? WHERE id_user = ?');
            $stmt->execute([$nome, $id_user]);

            $_SESSION['usuario']['nome'] = $nome;
            voltarComSucesso('nome');

        case 'email':
            $email = trim($_POST['email'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 70) {
                voltarComErro('email');
            }

            $stmt = $conn->prepare('SELECT id_user FROM Usuario WHERE email_user = ? AND id_user <> ? LIMIT 1');
            $stmt->execute([$email, $id_user]);

            if ($stmt->fetch()) {
                voltarComErro('email_existente');
            }

            $stmt = $conn->prepare('UPDATE Usuario SET email_user = ? WHERE id_user = ?');
            $stmt->execute([$email, $id_user]);

            $_SESSION['usuario']['email'] = $email;
            voltarComSucesso('email');

        case 'telefone':
            $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

            if (strlen($telefone) !== 11) {
                voltarComErro('telefone');
            }

            $stmt = $conn->prepare('UPDATE Usuario SET tel_user = ? WHERE id_user = ?');
            $stmt->execute([$telefone, $id_user]);
            voltarComSucesso('telefone');

        case 'senha':
            $senha = $_POST['senha'] ?? '';
            $confirmarSenha = $_POST['confirmar_senha'] ?? '';

            if (strlen($senha) < 6) {
                voltarComErro('senha');
            }

            if (!hash_equals($senha, $confirmarSenha)) {
                voltarComErro('senhas_diferentes');
            }

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE Usuario SET senha_user = ? WHERE id_user = ?');
            $stmt->execute([$hash, $id_user]);
            voltarComSucesso('senha');

        case 'data':
            $dataNascimento = $_POST['data_nascimento'] ?? '';
            $data = DateTime::createFromFormat('Y-m-d', $dataNascimento);
            $dataValida = $data && $data->format('Y-m-d') === $dataNascimento;

            if (!$dataValida || $dataNascimento > date('Y-m-d')) {
                voltarComErro('data');
            }

            $stmt = $conn->prepare('UPDATE Usuario SET date_user = ? WHERE id_user = ?');
            $stmt->execute([$dataNascimento, $id_user]);
            voltarComSucesso('data');

        default:
            voltarComErro('salvar');
    }
} catch (PDOException $e) {
    voltarComErro('salvar');
}
