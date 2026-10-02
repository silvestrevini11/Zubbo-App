<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/_regras-equipes.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$idEvento = filter_var($_POST['id_evento'] ?? null, FILTER_VALIDATE_INT);
$acao = (string) ($_POST['acao'] ?? '');
$origem = ($_POST['origem'] ?? '') === 'detalhes' ? 'detalhes' : 'lista';
$csrf = $_POST['csrf'] ?? '';

function vaga_flash(int $idEvento, string $tipo, string $mensagem): void
{
    $_SESSION['flash_vaga_evento'][$idEvento] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem,
    ];
}

function vaga_redirecionar(int $idEvento, string $origem): never
{
    $arquivo = $origem === 'detalhes' ? 'detalhes-evento.php' : 'lista-presenca.php';
    header('Location: ' . $arquivo . '?id_evento=' . $idEvento, true, 303);
    exit;
}

if (!$idEvento || $idEvento < 1) {
    http_response_code(400);
    exit('Evento inválido.');
}

if (
    !is_string($csrf)
    || empty($_SESSION['csrf_eventos'])
    || !hash_equals($_SESSION['csrf_eventos'], $csrf)
) {
    http_response_code(403);
    vaga_flash($idEvento, 'erro', 'A sessão expirou. Atualize a página e tente novamente.');
    vaga_redirecionar($idEvento, $origem);
}

$acoesPermitidas = ['solicitar', 'cancelar', 'aprovar', 'recusar', 'remover'];

if (!in_array($acao, $acoesPermitidas, true)) {
    http_response_code(400);
    vaga_flash($idEvento, 'erro', 'Ação inválida.');
    vaga_redirecionar($idEvento, $origem);
}

try {
    $conn->beginTransaction();

    // Bloquear o evento serializa solicitações e impede duas pessoas de ocuparem a mesma vaga.
    $stmt = $conn->prepare("
        SELECT ev.id_evento, ev.id_criador, ev.status_evento,
               (TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()) AS aberto,
               e.nome_esporte
        FROM Evento ev
        INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
        WHERE ev.id_evento = ?
        FOR UPDATE
    ");
    $stmt->execute([$idEvento]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        throw new RuntimeException('Evento não encontrado.');
    }

    $limite = evento_vagas_por_time((string) $evento['nome_esporte']);
    $organizador = (int) $evento['id_criador'] === $idUsuario;

    if ($limite === null) {
        throw new RuntimeException('Esta modalidade não usa a escalação em dois times.');
    }

    if ($evento['status_evento'] !== 'ativo' || !(bool) $evento['aberto']) {
        throw new RuntimeException('As solicitações deste evento estão encerradas.');
    }

    if ($acao === 'solicitar') {
        $time = filter_var($_POST['time_num'] ?? null, FILTER_VALIDATE_INT);
        $vaga = filter_var($_POST['numero_vaga'] ?? null, FILTER_VALIDATE_INT);

        if (!in_array($time, [1, 2], true) || !$vaga || $vaga < 1 || $vaga > $limite) {
            throw new RuntimeException('A vaga escolhida é inválida.');
        }

        $stmt = $conn->prepare("
            SELECT id_user
            FROM Usuario
            WHERE id_user = ? AND status_user = 'ativo'
            LIMIT 1
        ");
        $stmt->execute([$idUsuario]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Sua conta não está disponível para entrar neste evento.');
        }

        $stmt = $conn->prepare("
            SELECT *
            FROM Solicitacao_Vaga_Evento
            WHERE id_evento = ? AND id_user = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$idEvento, $idUsuario]);
        $solicitacaoUsuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($solicitacaoUsuario && $solicitacaoUsuario['status_solicitacao'] === 'pendente') {
            throw new RuntimeException('Você já possui uma solicitação pendente neste evento.');
        }

        if ($solicitacaoUsuario && $solicitacaoUsuario['status_solicitacao'] === 'aprovada') {
            throw new RuntimeException('Você já está confirmado em uma vaga deste evento.');
        }

        $stmt = $conn->prepare("
            SELECT s.id_solicitacao, s.status_solicitacao, u.nome_user
            FROM Solicitacao_Vaga_Evento s
            INNER JOIN Usuario u ON u.id_user = s.id_user
            WHERE s.id_evento = ?
              AND s.time_num = ?
              AND s.numero_vaga = ?
              AND s.status_solicitacao IN ('pendente', 'aprovada')
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$idEvento, $time, $vaga]);

        if ($stmt->fetch()) {
            throw new RuntimeException('Essa vaga já recebeu uma solicitação ou já está confirmada.');
        }

        if ($solicitacaoUsuario) {
            $stmt = $conn->prepare("
                UPDATE Solicitacao_Vaga_Evento
                SET time_num = ?,
                    numero_vaga = ?,
                    status_solicitacao = 'pendente',
                    data_solicitacao = CURRENT_TIMESTAMP,
                    data_resposta = NULL
                WHERE id_solicitacao = ?
            ");
            $stmt->execute([$time, $vaga, (int) $solicitacaoUsuario['id_solicitacao']]);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO Solicitacao_Vaga_Evento
                    (id_evento, id_user, time_num, numero_vaga)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$idEvento, $idUsuario, $time, $vaga]);
        }

        vaga_flash($idEvento, 'sucesso', 'Solicitação enviada. O organizador precisa aprovar sua vaga.');
    }

    if ($acao === 'cancelar') {
        $stmt = $conn->prepare("
            SELECT id_solicitacao, status_solicitacao
            FROM Solicitacao_Vaga_Evento
            WHERE id_evento = ? AND id_user = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$idEvento, $idUsuario]);
        $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$solicitacao || !in_array($solicitacao['status_solicitacao'], ['pendente', 'aprovada'], true)) {
            throw new RuntimeException('Você não possui uma vaga ativa neste evento.');
        }

        $stmt = $conn->prepare("
            UPDATE Solicitacao_Vaga_Evento
            SET status_solicitacao = 'cancelada', data_resposta = CURRENT_TIMESTAMP
            WHERE id_solicitacao = ?
        ");
        $stmt->execute([(int) $solicitacao['id_solicitacao']]);

        $stmt = $conn->prepare('DELETE FROM Lista_Evento WHERE id_evento = ? AND id_user = ?');
        $stmt->execute([$idEvento, $idUsuario]);

        vaga_flash(
            $idEvento,
            'sucesso',
            $solicitacao['status_solicitacao'] === 'aprovada'
                ? 'Você saiu da escalação e a vaga ficou livre novamente.'
                : 'Sua solicitação foi cancelada.'
        );
    }

    if (in_array($acao, ['aprovar', 'recusar', 'remover'], true)) {
        if (!$organizador) {
            throw new RuntimeException('Somente o organizador pode responder solicitações.');
        }

        $idSolicitacao = filter_var($_POST['id_solicitacao'] ?? null, FILTER_VALIDATE_INT);

        if (!$idSolicitacao || $idSolicitacao < 1) {
            throw new RuntimeException('Solicitação inválida.');
        }

        $stmt = $conn->prepare("
            SELECT *
            FROM Solicitacao_Vaga_Evento
            WHERE id_solicitacao = ? AND id_evento = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$idSolicitacao, $idEvento]);
        $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$solicitacao) {
            throw new RuntimeException('Solicitação não encontrada.');
        }

        if ($acao === 'aprovar') {
            if ($solicitacao['status_solicitacao'] !== 'pendente') {
                throw new RuntimeException('Essa solicitação já foi respondida.');
            }

            $stmt = $conn->prepare("
                SELECT 1
                FROM Solicitacao_Vaga_Evento
                WHERE id_evento = ?
                  AND time_num = ?
                  AND numero_vaga = ?
                  AND status_solicitacao = 'aprovada'
                  AND id_solicitacao <> ?
                LIMIT 1
            ");
            $stmt->execute([
                $idEvento,
                (int) $solicitacao['time_num'],
                (int) $solicitacao['numero_vaga'],
                $idSolicitacao,
            ]);

            if ($stmt->fetchColumn()) {
                throw new RuntimeException('Essa vaga já foi confirmada para outro usuário.');
            }

            $stmt = $conn->prepare("
                UPDATE Solicitacao_Vaga_Evento
                SET status_solicitacao = 'aprovada', data_resposta = CURRENT_TIMESTAMP
                WHERE id_solicitacao = ?
            ");
            $stmt->execute([$idSolicitacao]);

            $stmt = $conn->prepare("
                INSERT INTO Lista_Evento (id_user, id_evento)
                SELECT ?, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM Lista_Evento WHERE id_user = ? AND id_evento = ?
                )
            ");
            $stmt->execute([
                (int) $solicitacao['id_user'],
                $idEvento,
                (int) $solicitacao['id_user'],
                $idEvento,
            ]);

            vaga_flash($idEvento, 'sucesso', 'Solicitação aprovada e jogador confirmado na escalação.');
        }

        if ($acao === 'recusar') {
            if ($solicitacao['status_solicitacao'] !== 'pendente') {
                throw new RuntimeException('Essa solicitação já foi respondida.');
            }

            $stmt = $conn->prepare("
                UPDATE Solicitacao_Vaga_Evento
                SET status_solicitacao = 'recusada', data_resposta = CURRENT_TIMESTAMP
                WHERE id_solicitacao = ?
            ");
            $stmt->execute([$idSolicitacao]);

            vaga_flash($idEvento, 'sucesso', 'Solicitação recusada. A vaga está livre novamente.');
        }

        if ($acao === 'remover') {
            if ($solicitacao['status_solicitacao'] !== 'aprovada') {
                throw new RuntimeException('Esse usuário não ocupa uma vaga confirmada.');
            }

            $stmt = $conn->prepare("
                UPDATE Solicitacao_Vaga_Evento
                SET status_solicitacao = 'cancelada', data_resposta = CURRENT_TIMESTAMP
                WHERE id_solicitacao = ?
            ");
            $stmt->execute([$idSolicitacao]);

            $stmt = $conn->prepare('DELETE FROM Lista_Evento WHERE id_evento = ? AND id_user = ?');
            $stmt->execute([$idEvento, (int) $solicitacao['id_user']]);

            vaga_flash($idEvento, 'sucesso', 'Jogador removido da escalação.');
        }
    }

    $conn->commit();
} catch (RuntimeException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    vaga_flash($idEvento, 'erro', $e->getMessage());
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('Erro na escalação do evento: ' . $e->getMessage());
    vaga_flash($idEvento, 'erro', 'Não foi possível concluir a ação. Tente novamente.');
}

vaga_redirecionar($idEvento, $origem);
