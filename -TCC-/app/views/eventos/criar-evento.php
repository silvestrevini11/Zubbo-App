<?php
require_once __DIR__ . '/../../middleware/auth.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$erro = '';

if (empty($_SESSION['csrf_criar_evento'])) {
    $_SESSION['csrf_criar_evento'] = bin2hex(random_bytes(32));
}

try {
    $stmtEsportes = $conn->query("
        SELECT id_esporte, nome_esporte
        FROM Esporte
        ORDER BY nome_esporte
    ");
    $esportes = $stmtEsportes->fetchAll(PDO::FETCH_ASSOC);

    $stmtLocais = $conn->query("
        SELECT id_local, nome_local, endereco_local
        FROM LocalEsp
        WHERE status_local = 'aprovado'
        ORDER BY nome_local
    ");
    $locais = $stmtLocais->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao carregar opções para criar evento: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar o formulário de evento.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    $nomeEvento = trim((string) ($_POST['nome_evento'] ?? ''));
    $dataEvento = (string) ($_POST['data_evento'] ?? '');
    $horarioEvento = (string) ($_POST['horario_evento'] ?? '');
    $idEsporte = filter_var($_POST['id_esporte'] ?? null, FILTER_VALIDATE_INT);
    $idLocal = filter_var($_POST['id_local'] ?? null, FILTER_VALIDATE_INT);
    $duracao = filter_var($_POST['duracao_minutos'] ?? 60, FILTER_VALIDATE_INT);

    if (!is_string($csrf) || !hash_equals($_SESSION['csrf_criar_evento'], $csrf)) {
        http_response_code(403);
        $erro = 'A sessão do formulário expirou. Atualize a página.';
    } elseif (mb_strlen($nomeEvento) < 3 || mb_strlen($nomeEvento) > 100) {
        $erro = 'O nome do evento deve ter entre 3 e 100 caracteres.';
    } elseif (!$idEsporte || !$idLocal || !in_array($duracao, [60, 90, 120], true)) {
        $erro = 'Selecione um esporte e um local válidos.';
    } else {
        $dataHora = DateTime::createFromFormat('Y-m-d H:i', $dataEvento . ' ' . $horarioEvento);
        $errosData = DateTime::getLastErrors();

        if (
            !$dataHora
            || ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
            || $dataHora <= new DateTime('now')
            || ((int)substr($horarioEvento,0,2)*60+(int)substr($horarioEvento,3,2)+$duracao > 1440)
        ) {
            $erro = 'Escolha uma data e um horário futuros.';
        }
    }

    if ($erro === '') {
        try {
            $conn->beginTransaction();
            // Bloqueio por local serializa criações simultâneas nesse poliesportivo.
            $bloquearLocal = $conn->prepare("SELECT id_local FROM LocalEsp WHERE id_local = ? AND status_local = 'aprovado' FOR UPDATE");
            $bloquearLocal->execute([$idLocal]);
            if (!$bloquearLocal->fetchColumn()) {
                throw new RuntimeException('O local não está disponível.');
            }
            $stmt = $conn->prepare('SELECT 1 FROM Esporte WHERE id_esporte = ? LIMIT 1');
            $stmt->execute([$idEsporte]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('O esporte selecionado não existe.');
            }
            // Modalidades que exigem confirmação só podem ser usadas em locais
            // cuja aceitação foi registrada pelo ZubboADM.
            $verificarAceite=$conn->prepare("SELECT 1 FROM Esporte e
              WHERE e.id_esporte = ? AND (e.exige_confirmacao_local = 0 OR
                EXISTS (SELECT 1 FROM Local_Esporte_Confirmacao lc
                  WHERE lc.id_esporte = e.id_esporte AND lc.id_local = ?))");
            $verificarAceite->execute([$idEsporte,$idLocal]);
            if (!$verificarAceite->fetchColumn()) {
                throw new RuntimeException('Esta modalidade ainda não foi confirmada pelo administrador para o local escolhido.');
            }

            $stmt = $conn->prepare("
                SELECT 1
                FROM LocalEsp
                WHERE id_local = ? AND status_local = 'aprovado'
                LIMIT 1
            ");
            $stmt->execute([$idLocal]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('O local selecionado não está disponível.');
            }

            // A linha LocalEsp permanece bloqueada até o COMMIT, impedindo
            // criação simultânea de dois eventos no mesmo intervalo do local.
            // Reserva interna do Zubbo; não equivale a reserva oficial da quadra.
            $checar = $conn->prepare("
                SELECT id_evento, nome_evento, data_evento, horario_evento, duracao_minutos
                FROM Evento
                WHERE id_local = ?
                  AND data_evento = ?
                  AND status_evento = 'ativo'
                  AND TIME_TO_SEC(horario_evento) < TIME_TO_SEC(?) + ? * 60
                  AND TIME_TO_SEC(horario_evento) + duracao_minutos * 60 > TIME_TO_SEC(?)
                ORDER BY horario_evento, id_evento
                LIMIT 1
            ");
            $checar->execute([$idLocal, $dataEvento, $horarioEvento, $duracao, $horarioEvento]);
            $conflito = $checar->fetch(PDO::FETCH_ASSOC);
            if ($conflito) {
                $inicioOcupado = substr((string)$conflito['horario_evento'], 0, 5);
                $fimOcupado = (new DateTimeImmutable(
                    $conflito['data_evento'] . ' ' . $conflito['horario_evento']
                ))->modify('+' . (int)$conflito['duracao_minutos'] . ' minutes')->format('H:i');
                throw new RuntimeException(
                    'Não foi possível criar o evento: este local já possui "' .
                    $conflito['nome_evento'] . '" das ' . $inicioOcupado .
                    ' às ' . $fimOcupado . '. Escolha outro horário ou outro local.'
                );
            }

            $stmt = $conn->prepare("
                INSERT INTO Evento (
                    nome_evento,
                    data_evento,
                    horario_evento,
                    duracao_minutos,
                    id_esporte,
                    id_local,
                    id_criador
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nomeEvento,
                $dataEvento,
                $horarioEvento,
                $duracao,
                $idEsporte,
                $idLocal,
                $idUsuario,
            ]);

            $novoEvento = (int) $conn->lastInsertId();
            $conn->commit();
            $_SESSION['csrf_criar_evento'] = bin2hex(random_bytes(32));

            header('Location: detalhes-evento.php?id_evento=' . $novoEvento, true, 303);
            exit;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log('Erro ao criar evento: ' . $e->getMessage());
            $erro = 'Não foi possível criar o evento. Tente novamente.';
        } catch (RuntimeException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $erro = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/head.php';

function escaparCriarEvento($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>

<section class="evento-criar-container">
    <h1>Criar evento</h1>

    <?php if ($erro): ?>
        <p class="evento-erro" role="alert"><?= escaparCriarEvento($erro) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= escaparCriarEvento($_SESSION['csrf_criar_evento']) ?>">

        <div>
            <label for="nome_evento">Nome do evento</label>
            <input
                type="text"
                name="nome_evento"
                id="nome_evento"
                minlength="3"
                maxlength="100"
                value="<?= escaparCriarEvento($_POST['nome_evento'] ?? '') ?>"
                required
            >
        </div>

        <div>
            <label for="data_evento">Data</label>
            <input
                type="date"
                name="data_evento"
                id="data_evento"
                min="<?= date('Y-m-d') ?>"
                value="<?= escaparCriarEvento($_POST['data_evento'] ?? '') ?>"
                required
            >
        </div>

        <div>
            <label for="horario_evento">Horário</label>
            <input
                type="time"
                name="horario_evento"
                id="horario_evento"
                value="<?= escaparCriarEvento($_POST['horario_evento'] ?? '') ?>"
                required
            >
        </div>

        <div>
            <label for="id_esporte">Esporte</label>
            <select name="id_esporte" id="id_esporte" required>
                <option value="">Selecione um esporte</option>
                <?php foreach ($esportes as $esporte): ?>
                    <option
                        value="<?= (int) $esporte['id_esporte'] ?>"
                        <?= (string) ($_POST['id_esporte'] ?? '') === (string) $esporte['id_esporte'] ? 'selected' : '' ?>
                    >
                        <?= escaparCriarEvento($esporte['nome_esporte']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_local">Local</label>
            <select name="id_local" id="id_local" required>
                <option value="">Selecione um local</option>
                <?php foreach ($locais as $local): ?>
                    <option
                        value="<?= (int) $local['id_local'] ?>"
                        <?= (string) ($_POST['id_local'] ?? '') === (string) $local['id_local'] ? 'selected' : '' ?>
                    >
                        <?= escaparCriarEvento($local['nome_local']) ?> — <?= escaparCriarEvento($local['endereco_local']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="duracao_minutos">Duração estimada</label>
            <select id="duracao_minutos" name="duracao_minutos" required>
                <?php foreach ([60 => '1 hora', 90 => '1 hora e 30 minutos', 120 => '2 horas'] as $minutos => $texto): ?>
                    <option value="<?= $minutos ?>" <?= (int)($_POST['duracao_minutos'] ?? 60) === $minutos ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Criar evento</button>
    </form>
</section>

<?php
include __DIR__ . '/../includes/under-bar.php';
include __DIR__ . '/../includes/footer.php';
?>
