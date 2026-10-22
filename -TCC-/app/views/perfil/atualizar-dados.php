<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil-editar.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/logger.php';

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
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
            $consultaSenha = $conn->prepare('SELECT senha_user, email_user FROM Usuario WHERE id_user = ?');
            $consultaSenha->execute([$id_user]);
            $atual = $consultaSenha->fetch(PDO::FETCH_ASSOC);

            if (!$atual || !password_verify($senhaAtual, (string) $atual['senha_user'])) {
                zubbo_log('warning', 'account.email_change_denied', ['user_id' => $id_user, 'reason_code' => 'wrong_password']);
                voltarComErro('senha_atual');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 70) {
                voltarComErro('email');
            }
            if ($email === strtolower((string) $atual['email_user'])) {
                voltarComSucesso('email');
            }

            $stmt = $conn->prepare('SELECT id_user FROM Usuario WHERE email_user = ? AND id_user <> ? LIMIT 1');
            $stmt->execute([$email, $id_user]);
            if ($stmt->fetch()) {
                voltarComErro('email_existente');
            }
            if (zubbo_rate_limit_exceeded('alteracao_email', 5, 3600, (string) $id_user)) {
                voltarComErro('limite');
            }
            zubbo_rate_limit_hit('alteracao_email', 3600, (string) $id_user);

            $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['alteracao_email'] = [
                'id_user' => $id_user,
                'novo_email' => $email,
                'codigo_hash' => hash('sha256', $codigo),
                'expira_em' => time() + 600,
                'tentativas' => 0,
            ];

            if (filter_var((string) getenv('ZUBBO_DEMO_MODE'), FILTER_VALIDATE_BOOLEAN)) {
                $_SESSION['codigo_email_demo'] = $codigo;
            } else {
                require_once __DIR__ . '/../../../vendor/autoload.php';
                require_once __DIR__ . '/../../../config/mail.php';
                try {
                    zubbo_enviar_email(
                        $email,
                        'Confirme seu novo e-mail - Zubbo',
                        '<p>Seu código para confirmar a alteração de e-mail é <strong>' .
                            htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '</strong>.</p><p>Válido por 10 minutos.</p>',
                        'Seu código para confirmar o novo e-mail no Zubbo é: ' . $codigo
                    );
                } catch (Throwable $e) {
                    unset($_SESSION['alteracao_email'], $_SESSION['codigo_email_demo']);
                    zubbo_log('error', 'account.email_delivery_failed', ['user_id' => $id_user, 'reason_code' => 'provider_error']);
                    voltarComErro('email_envio');
                }
            }
            zubbo_log('info', 'account.email_change_requested', ['user_id' => $id_user]);
            header('Location: confirmar-email.php', true, 303);
            exit;

        case 'telefone':
            $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');

            if (strlen($telefone) !== 11) {
                voltarComErro('telefone');
            }

            $stmt = $conn->prepare('UPDATE Usuario SET tel_user = ? WHERE id_user = ?');
            $stmt->execute([$telefone, $id_user]);
            voltarComSucesso('telefone');

        case 'senha':
            $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
            $senha = (string) ($_POST['senha'] ?? '');
            $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

            $stmtSenha = $conn->prepare('SELECT senha_user FROM Usuario WHERE id_user = ? LIMIT 1');
            $stmtSenha->execute([$id_user]);
            $hashAntigo = (string) ($stmtSenha->fetchColumn() ?: '');
            if ($hashAntigo === '' || !password_verify($senhaAtual, $hashAntigo)) {
                zubbo_log('warning', 'account.password_change_denied', ['user_id' => $id_user, 'reason_code' => 'wrong_password']);
                voltarComErro('senha_atual');
            }
            if (strlen($senha) < 8 || strlen($senha) > 255) {
                voltarComErro('senha');
            }
            if (!hash_equals($senha, $confirmarSenha)) {
                voltarComErro('senhas_diferentes');
            }
            if (password_verify($senha, $hashAntigo)) {
                voltarComErro('senha_repetida');
            }

            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE Usuario SET senha_user = ? WHERE id_user = ? AND senha_user = ?');
            $stmt->execute([$hash, $id_user, $hashAntigo]);
            if ($stmt->rowCount() !== 1) {
                voltarComErro('salvar');
            }
            session_regenerate_id(true);
            $_SESSION['credential_fingerprint'] = hash('sha256', $hash);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            zubbo_log('info', 'account.password_changed', ['user_id' => $id_user]);
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

                    case 'sobre_mim':
            $sobreMim = trim($_POST['sobre_mim'] ?? '');
            $tamanho = function_exists('mb_strlen')
                ? mb_strlen($sobreMim, 'UTF-8')
                : strlen($sobreMim);

            if ($tamanho > 500) {
                voltarComErro('sobre_mim');
            }

            $stmt = $conn->prepare('
                UPDATE Usuario
                SET sobre_mim = ?
                WHERE id_user = ?
            ');

            $stmt->execute([
                $sobreMim !== '' ? $sobreMim : null,
                $id_user
            ]);

            voltarComSucesso('sobre_mim');

        default:
            voltarComErro('salvar');
    }
} catch (Throwable $e) {
    zubbo_log('error', 'account.update_failed', ['user_id' => $id_user, 'reason_code' => 'internal_error']);
    voltarComErro('salvar');
}
