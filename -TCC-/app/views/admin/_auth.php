<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
require_once __DIR__ . '/../../../config/database.php';

if (
    empty($_SESSION['usuario']['id'])
    || empty($_SESSION['usuario']['email'])
) {
    unset($_SESSION['admin'], $_SESSION['admin_csrf_token']);
    header('Location: ../auth/login.php');
    exit;
}

$stmtAdminAtual = $conn->prepare(
    'SELECT id_adm
     FROM Administrador
     WHERE email_adm = ?
       AND ativo = 1
     LIMIT 1'
);
$stmtAdminAtual->execute([$_SESSION['usuario']['email']]);
$idAdminAtual = $stmtAdminAtual->fetchColumn();

if ($idAdminAtual === false) {
    unset($_SESSION['admin'], $_SESSION['admin_csrf_token'], $_SESSION['admin_flash']);
    header('Location: ../painel/painel-inicial.php');
    exit;
}

$_SESSION['admin'] = [
    'id' => (int) $idAdminAtual,
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
    $_SESSION['admin_flash'] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem,
    ];
}

function admin_registrar_acao(PDO $conn, string $tipo, string $motivo, array $alvos = []): void
{
    $sql = "
        INSERT INTO Acao_Administrativa
            (id_adm, id_user, id_denuncia, id_evento, id_local, id_grupo, id_comunidade, tipo_acao, motivo)
        VALUES
            (:id_adm, :id_user, :id_denuncia, :id_evento, :id_local, :id_grupo, :id_comunidade, :tipo_acao, :motivo)
    ";

    $stmt = $conn->prepare($sql);
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
}
