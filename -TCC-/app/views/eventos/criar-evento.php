<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

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

    if (!is_string($csrf) || !hash_equals($_SESSION['csrf_criar_evento'], $csrf)) {
        http_response_code(403);
        $erro = 'A sessão do formulário expirou. Atualize a página.';
    } elseif (mb_strlen($nomeEvento) < 3 || mb_strlen($nomeEvento) > 100) {
        $erro = 'O nome do evento deve ter entre 3 e 100 caracteres.';
    } elseif (!$idEsporte || !$idLocal) {
        $erro = 'Selecione um esporte e um local válidos.';
    } else {
        $dataHora = DateTime::createFromFormat('Y-m-d H:i', $dataEvento . ' ' . $horarioEvento);
        $errosData = DateTime::getLastErrors();

        if (
            !$dataHora
            || ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
            || $dataHora <= new DateTime('now')
        ) {
            $erro = 'Escolha uma data e um horário futuros.';
        }
    }

    if ($erro === '') {
        try {
            $stmt = $conn->prepare('SELECT 1 FROM Esporte WHERE id_esporte = ? LIMIT 1');
            $stmt->execute([$idEsporte]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('O esporte selecionado não existe.');
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

            $stmt = $conn->prepare("
                INSERT INTO Evento (
                    nome_evento,
                    data_evento,
                    horario_evento,
                    id_esporte,
                    id_local,
                    id_criador
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nomeEvento,
                $dataEvento,
                $horarioEvento,
                $idEsporte,
                $idLocal,
                $idUsuario,
            ]);

            $novoEvento = (int) $conn->lastInsertId();
            $_SESSION['csrf_criar_evento'] = bin2hex(random_bytes(32));

            header('Location: detalhes-evento.php?id_evento=' . $novoEvento, true, 303);
            exit;
        } catch (PDOException $e) {
            error_log('Erro ao criar evento: ' . $e->getMessage());
            $erro = 'Não foi possível criar o evento. Tente novamente.';
        } catch (RuntimeException $e) {
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

        <button type="submit">Criar evento</button>
    </form>
</section>

<?php
include __DIR__ . '/../includes/under-bar.php';
include __DIR__ . '/../includes/footer.php';
?>