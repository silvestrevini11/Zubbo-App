<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../services/LocalService.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$erro = '';

$stmtEsportes = $conn->query(
    'SELECT id_esporte, nome_esporte FROM Esporte ORDER BY nome_esporte'
);
$esportes = $stmtEsportes->fetchAll(PDO::FETCH_ASSOC);
$locais = LocalService::listarAprovados($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    zubbo_require_csrf();

    $nomeEvento = trim((string) ($_POST['nome_evento'] ?? ''));
    $dataEvento = (string) ($_POST['data_evento'] ?? '');
    $horarioEvento = (string) ($_POST['horario_evento'] ?? '');
    $idEsporte = (int) ($_POST['id_esporte'] ?? 0);
    $idLocal = (int) ($_POST['id_local'] ?? 0);

    $data = DateTime::createFromFormat('Y-m-d', $dataEvento);
    $dataValida = $data && $data->format('Y-m-d') === $dataEvento;
    $horarioValido = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horarioEvento) === 1;

    $stmtEsporte = $conn->prepare('SELECT 1 FROM Esporte WHERE id_esporte = ? LIMIT 1');
    $stmtEsporte->execute([$idEsporte]);
    $esporteExiste = (bool) $stmtEsporte->fetchColumn();

    if (
        $nomeEvento === ''
        || mb_strlen($nomeEvento) > 100
        || !$dataValida
        || !$horarioValido
        || $dataEvento < date('Y-m-d')
        || !$esporteExiste
        || !LocalService::aprovadoExiste($conn, $idLocal)
    ) {
        $erro = 'Confira os dados do evento e escolha um local aprovado.';
    } else {
        try {
            $stmt = $conn->prepare(
                'INSERT INTO Evento
                    (nome_evento, data_evento, horario_evento, id_esporte, id_local, id_criador)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $nomeEvento,
                $dataEvento,
                $horarioEvento,
                $idEsporte,
                $idLocal,
                $idUsuario,
            ]);

            header('Location: eventos.php', true, 303);
            exit;
        } catch (PDOException $e) {
            error_log('Erro ao criar evento: ' . $e->getMessage());
            $erro = 'Não foi possível criar o evento agora. Tente novamente.';
        }
    }
}

include __DIR__ . '/../includes/head.php';
?>
<section class="evento-criar-container">
    <h1>Criar evento</h1>

    <?php if ($erro !== ''): ?>
        <p class="evento-erro" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <?= zubbo_csrf_input() ?>

        <div>
            <label for="nome_evento">Nome do evento</label>
            <input type="text" name="nome_evento" id="nome_evento" maxlength="100" required>
        </div>

        <div>
            <label for="data_evento">Data</label>
            <input type="date" name="data_evento" id="data_evento" min="<?= date('Y-m-d') ?>" required>
        </div>

        <div>
            <label for="horario_evento">Horário</label>
            <input type="time" name="horario_evento" id="horario_evento" required>
        </div>

        <div>
            <label for="id_esporte">Esporte</label>
            <select name="id_esporte" id="id_esporte" required>
                <option value="">Selecione um esporte</option>
                <?php foreach ($esportes as $esporte): ?>
                    <option value="<?= (int) $esporte['id_esporte'] ?>">
                        <?= htmlspecialchars($esporte['nome_esporte'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_local">Local</label>
            <select name="id_local" id="id_local" required>
                <option value="">Selecione um local aprovado</option>
                <?php foreach ($locais as $local): ?>
                    <option value="<?= (int) $local['id_local'] ?>">
                        <?= htmlspecialchars($local['nome_local'], ENT_QUOTES, 'UTF-8') ?>
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
