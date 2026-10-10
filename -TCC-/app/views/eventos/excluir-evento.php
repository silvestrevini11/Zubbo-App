<?php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/auth.php';

// Permite exclusão somente por requisição POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido.');
}

// Verifica se existe um usuário autenticado.
if (empty($_SESSION['usuario']['id'])) {
    http_response_code(401);
    exit('Você precisa estar autenticado.');
}

$idUsuario = (int) $_SESSION['usuario']['id'];

$idEvento = filter_var(
    $_POST['id_evento'] ?? null,
    FILTER_VALIDATE_INT
);

$csrf = $_POST['csrf'] ?? '';

// Valida o ID do evento.
if (!$idEvento || $idEvento < 1) {
    http_response_code(400);
    exit('ID do evento inválido.');
}

// Valida o token CSRF.
if (
    !is_string($csrf) ||
    empty($_SESSION['csrf_eventos']) ||
    !is_string($_SESSION['csrf_eventos']) ||
    !hash_equals($_SESSION['csrf_eventos'], $csrf)
) {
    http_response_code(403);
    exit('Solicitação inválida. Atualize a página e tente novamente.');
}

try {
    $conn->beginTransaction();

    // Verifica se o evento existe e pertence ao usuário logado.
    $stmt = $conn->prepare("
        SELECT id_evento
        FROM Evento
        WHERE id_evento = ?
          AND id_criador = ?
        FOR UPDATE
    ");

    $stmt->execute([$idEvento, $idUsuario]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new DomainException(
            'Evento não encontrado ou você não é o organizador.'
        );
    }

    // Exclui fisicamente o evento.
    // As chaves estrangeiras ON DELETE CASCADE removem
    // os registros dependentes configurados no banco.
    $stmt = $conn->prepare("
        DELETE FROM Evento
        WHERE id_evento = ?
          AND id_criador = ?
    ");

    $stmt->execute([$idEvento, $idUsuario]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            'Não foi possível excluir o evento.'
        );
    }

    // Confirma a exclusão.
    $conn->commit();

    // Gera outro token CSRF após a operação.
    $_SESSION['csrf_eventos'] = bin2hex(random_bytes(32));

    // Retorna à listagem.
    header('Location: eventos.php', true, 303);
    exit;

} catch (DomainException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(403);
    exit(htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    ));

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        'Erro de banco ao excluir evento: ' . $e->getMessage()
    );

    http_response_code(500);
    exit(
        'Não foi possível excluir o evento. Verifique as dependências no banco de dados.'
    );

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        'Erro ao excluir evento: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('Ocorreu um erro ao excluir o evento.');
}