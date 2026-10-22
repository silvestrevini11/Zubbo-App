<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../services/AdminService.php';

if (empty($_SESSION['usuario']['id']) || empty($_SESSION['usuario']['email'])) {
    unset($_SESSION['admin'], $_SESSION['admin_csrf_token']);
    header('Location: ../auth/login.php');
    exit;
}

$idAdminAtual = AdminService::buscarIdAtivo(
    $conn,
    (int) $_SESSION['usuario']['id'],
    (string) $_SESSION['usuario']['email']
);

if ($idAdminAtual === null) {
    unset($_SESSION['admin'], $_SESSION['admin_csrf_token'], $_SESSION['admin_flash']);
    require_once __DIR__ . '/../../../config/logger.php';
    zubbo_log('warning', 'admin.access_denied', ['user_id' => (int) $_SESSION['usuario']['id']]);
    header('Location: ../painel/Painel-inicial.php');
    exit;
}

$_SESSION['admin'] = [
    'id' => $idAdminAtual,
    'id_user' => (int) $_SESSION['usuario']['id'],
    'nome' => $_SESSION['usuario']['nome'] ?? 'Administrador',
    'email' => $_SESSION['usuario']['email'],
];

if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

function admin_csrf_valido(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['admin_csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token']);
}

function admin_exigir_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_csrf_valido()) {
        http_response_code(403);
        exit('Solicitação administrativa inválida.');
    }
}

function admin_flash(string $tipo, string $mensagem): void
{
    $_SESSION['admin_flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function admin_registrar_acao(PDO $conn, string $tipo, string $motivo, array $alvos = []): void
{
    $stmt = $conn->prepare("
        INSERT INTO Acao_Administrativa
            (id_adm, id_user, id_denuncia, id_evento, id_local, id_grupo, id_comunidade, tipo_acao, motivo)
        VALUES
            (:id_adm, :id_user, :id_denuncia, :id_evento, :id_local, :id_grupo, :id_comunidade, :tipo_acao, :motivo)
    ");

    $stmt->execute([
        ':id_adm' => (int) $_SESSION['admin']['id'],
        ':id_user' => $alvos['id_user'] ?? null,
        ':id_denuncia' => $alvos['id_denuncia'] ?? null,
        ':id_evento' => $alvos['id_evento'] ?? null,
        ':id_local' => $alvos['id_local'] ?? null,
        ':id_grupo' => $alvos['id_grupo'] ?? null,
        ':id_comunidade' => $alvos['id_comunidade'] ?? null,
        ':tipo_acao' => $tipo,
        ':motivo' => mb_substr($motivo, 0, 255),
    ]);
    require_once __DIR__ . '/../../../config/logger.php';
    zubbo_log('info', 'admin.action_logged', [
        'admin_id' => (int) $_SESSION['admin']['id'],
        'action' => $tipo,
        'entity_type' => isset($alvos['id_user']) ? 'user' : (isset($alvos['id_evento']) ? 'event' : 'other'),
    ]);
}
