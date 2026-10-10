<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
zubbo_require_csrf();
if (empty($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/logger.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$senha = (string) ($_POST['senha'] ?? '');

try {
    $conn->beginTransaction();
    $stmt = $conn->prepare('SELECT senha_user, foto_user FROM Usuario WHERE id_user = ? AND status_user = \'ativo\' FOR UPDATE');
    $stmt->execute([$idUsuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario || !password_verify($senha, (string) $usuario['senha_user'])) {
        $conn->rollBack();
        header('Location: perfil-configuracoes.php?erro=senha');
        exit;
    }

    $stmt = $conn->prepare('SELECT 1 FROM Administrador WHERE id_user = ? AND ativo = 1 LIMIT 1');
    $stmt->execute([$idUsuario]);
    if ($stmt->fetchColumn()) {
        $conn->rollBack();
        header('Location: perfil-configuracoes.php?erro=admin');
        exit;
    }

    // Exclusão com anonimização de dados de contato. Preserva referências necessárias
    // à análise de denúncias e ao histórico comunitário, sem manter a identidade pública.
    // Política definitiva de retenção e remoção de conteúdos deve ser definida antes
    // de produção aberta (LGPD).
    $emailAnonimo = 'conta-' . $idUsuario . '@deleted.invalid';
    $nomeAnonimo = 'Usuario desativado';
    $hashInvalido = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $conn->prepare("
        UPDATE Usuario
        SET nome_user=?, email_user=?, tel_user='', senha_user=?,
            date_user='1970-01-01', foto_user=NULL, sobre_mim=NULL,
            email_verificado=FALSE, status_user='banido'
        WHERE id_user=?
    ")->execute([$nomeAnonimo, $emailAnonimo, $hashInvalido, $idUsuario]);
    $conn->prepare('DELETE FROM Usuario_Esporte WHERE id_user = ?')->execute([$idUsuario]);
    $conn->prepare('DELETE FROM Amizade WHERE id_user_1 = ? OR id_user_2 = ?')
        ->execute([$idUsuario, $idUsuario]);
    $conn->prepare('DELETE FROM Solicitacao_Amizade WHERE id_remetente = ? OR id_destinatario = ?')
        ->execute([$idUsuario, $idUsuario]);
    $conn->commit();

    // Remoção de arquivo somente em diretório de uploads conhecido.
    $foto = (string) ($usuario['foto_user'] ?? '');
    if (preg_match('#^public/uploads/perfis/[A-Za-z0-9_.-]+\\.(?:jpg|png|webp)$#i', $foto)) {
        $caminho = __DIR__ . '/../../../' . $foto;
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }
    zubbo_log('info', 'account.deactivated', ['user_id' => $idUsuario]);
    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();
    header('Location: ../auth/login.php?conta=desativada', true, 303);
    exit;
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    zubbo_log('error', 'account.deactivation_failed', ['user_id' => $idUsuario, 'reason_code' => 'db_failure']);
    header('Location: perfil-configuracoes.php?erro=exclusao', true, 303);
    exit;
}
