<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
if (empty($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/logger.php';

$uid = (int) $_SESSION['usuario']['id'];
$pendente = $_SESSION['alteracao_email'] ?? null;
$erro = '';

if (!is_array($pendente) || (int) ($pendente['id_user'] ?? 0) !== $uid
    || (int) ($pendente['expira_em'] ?? 0) < time()) {
    unset($_SESSION['alteracao_email'], $_SESSION['codigo_email_demo']);
    header('Location: perfil-editar.php?erro=email_expirado');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    zubbo_require_csrf();
    $codigo = trim((string) ($_POST['codigo'] ?? ''));
    $tentativas = (int) ($pendente['tentativas'] ?? 0);
    if ($tentativas >= 5 || zubbo_rate_limit_exceeded('confirmacao_email', 5, 600, (string) $uid)) {
        unset($_SESSION['alteracao_email'], $_SESSION['codigo_email_demo']);
        header('Location: perfil-editar.php?erro=limite');
        exit;
    }

    if (!preg_match('/^[0-9]{6}$/D', $codigo)
        || !hash_equals((string) $pendente['codigo_hash'], hash('sha256', $codigo))) {
        $_SESSION['alteracao_email']['tentativas'] = $tentativas + 1;
        zubbo_rate_limit_hit('confirmacao_email', 600, (string) $uid);
        $erro = 'Código incorreto ou expirado.';
        zubbo_log('warning', 'account.email_confirmation_denied', ['user_id' => $uid, 'reason_code' => 'invalid_code']);
    } else {
        try {
            $novoEmail = (string) $pendente['novo_email'];
            $conn->beginTransaction();
            $stmt = $conn->prepare('SELECT id_user FROM Usuario WHERE email_user = ? AND id_user <> ? LIMIT 1');
            $stmt->execute([$novoEmail, $uid]);
            if ($stmt->fetch()) {
                throw new RuntimeException('email_unavailable');
            }
            $conn->prepare('UPDATE Usuario SET email_user = ?, email_verificado = TRUE WHERE id_user = ?')
                ->execute([$novoEmail, $uid]);
            // Mantém os dados cadastrais da conta administrativa sincronizados.
            $conn->prepare('UPDATE Administrador SET email_adm = ? WHERE id_user = ?')
                ->execute([$novoEmail, $uid]);
            $conn->commit();
            unset($_SESSION['alteracao_email'], $_SESSION['codigo_email_demo']);
            zubbo_rate_limit_reset('confirmacao_email', (string) $uid);
            session_regenerate_id(true);
            $_SESSION['usuario']['email'] = $novoEmail;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            zubbo_log('info', 'account.email_changed', ['user_id' => $uid]);
            header('Location: perfil-editar.php?sucesso=email', true, 303);
            exit;
        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            zubbo_log('error', 'account.email_change_failed', ['user_id' => $uid, 'reason_code' => 'db_failure']);
            $erro = 'Não foi possível confirmar a alteração. Verifique se o e-mail já foi utilizado.';
        }
    }
}
include __DIR__ . '/../includes/head.php';
?>
<main class="editar-dados-container">
    <header class="editar-dados-topo">
        <a class="editar-dados-voltar" href="perfil-editar.php" aria-label="Voltar">←</a>
    </header>
    <h1>Confirmar novo e-mail</h1>
    <p>Enviamos um código para <strong><?= htmlspecialchars((string) $pendente['novo_email'], ENT_QUOTES, 'UTF-8') ?></strong>. Seu e-mail atual continua válido até a confirmação.</p>
    <?php if ($erro): ?><p class="editar-dados-alerta editar-dados-erro" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (filter_var((string) getenv('ZUBBO_DEMO_MODE'), FILTER_VALIDATE_BOOLEAN) && isset($_SESSION['codigo_email_demo'])): ?>
      <p class="editar-dados-alerta editar-dados-sucesso"><strong>Somente na demo:</strong> código <?= htmlspecialchars((string) $_SESSION['codigo_email_demo'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form class="editar-dados-form" method="post">
        <?= zubbo_csrf_input() ?>
        <label for="codigo">Código de seis dígitos</label>
        <input id="codigo" name="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required>
        <button type="submit" class="editar-campo-btn">Confirmar alteração</button>
    </form>
</main>
<?php
include __DIR__ . '/../includes/under-bar.php';
include __DIR__ . '/../includes/footer.php';
