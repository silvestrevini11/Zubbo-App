<?php

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../usuario/form-usuario.php');
    exit;
}

include __DIR__ . '/../../../config/database.php';
include __DIR__ . '/../includes/head.php';

$id_usuario = (int) $_SESSION['usuario']['id'];


/* ==========================================
   BUSCAR ESPORTES

$stmtEsportes = $conn->prepare("
    SELECT id_esporte, nome_esporte
    FROM Esporte
    ORDER BY nome_esporte
");

$stmtEsportes->execute();

$esportes = $stmtEsportes->fetchAll(PDO::FETCH_ASSOC);


/* ==========================================
   BUSCAR LOCAIS

$stmtLocais = $conn->prepare("
    SELECT id_local, nome_local
    FROM LocalEsp
    ORDER BY nome_local
");

$stmtLocais->execute();

$locais = $stmtLocais->fetchAll(PDO::FETCH_ASSOC);


/* ==========================================
   CRIAR EVENTO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome_evento = trim($_POST['nome_evento'] ?? '');
    $data_evento = $_POST['data_evento'] ?? '';
    $horario_evento = $_POST['horario_evento'] ?? '';

    $id_esporte = (int) ($_POST['id_esporte'] ?? 0);
    $id_local = (int) ($_POST['id_local'] ?? 0);

    if (
        $nome_evento === '' ||
        $data_evento === '' ||
        $horario_evento === '' ||
        $id_esporte <= 0 ||
        $id_local <= 0
    ) {

        $erro = 'Preencha todos os campos.';

    } else {

        $stmt = $conn->prepare("
            INSERT INTO Evento (
                nome_evento,
                data_evento,
                horario_evento,
                id_esporte,
                id_local,
                id_criador
            )
            VALUES (
                :nome_evento,
                :data_evento,
                :horario_evento,
                :id_esporte,
                :id_local,
                :id_criador
            )
        ");

        $stmt->execute([
            ':nome_evento' => $nome_evento,
            ':data_evento' => $data_evento,
            ':horario_evento' => $horario_evento,
            ':id_esporte' => $id_esporte,
            ':id_local' => $id_local,
            ':id_criador' => $id_usuario
        ]);

        header('Location: eventos.php');
        exit;
    }
}

?>

<section class="evento-criar-container">

    <h1>
        Criar evento
    </h1>

    <?php if (!empty($erro)): ?>

        <p class="evento-erro">
            <?= htmlspecialchars($erro) ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <div>
            <label for="nome_evento">
                Nome do evento
            </label>

            <input
                type="text"
                name="nome_evento"
                id="nome_evento"
                maxlength="100"
                required
            >
        </div>


        <div>
            <label for="data_evento">
                Data
            </label>

            <input
                type="date"
                name="data_evento"
                id="data_evento"
                required
            >
        </div>


        <div>
            <label for="horario_evento">
                Horário
            </label>

            <input
                type="time"
                name="horario_evento"
                id="horario_evento"
                required
            >
        </div>


        <div>
            <label for="id_esporte">
                Esporte
            </label>

            <select
                name="id_esporte"
                id="id_esporte"
                required
            >

                <option value="">
                    Selecione um esporte
                </option>

                <?php foreach ($esportes as $esporte): ?>

                    <option value="<?= $esporte['id_esporte'] ?>">

                        <?= htmlspecialchars($esporte['nome_esporte']) ?>

                    </option>

                <?php endforeach; ?>

            </select>
        </div>


        <div>
            <label for="id_local">
                Local
            </label>

            <select
                name="id_local"
                id="id_local"
                required
            >

                <option value="">
                    Selecione um local
                </option>

                <?php foreach ($locais as $local): ?>

                    <option value="<?= $local['id_local'] ?>">

                        <?= htmlspecialchars($local['nome_local']) ?>

                    </option>

                <?php endforeach; ?>

            </select>
        </div>


        <button type="submit">
            Criar evento
        </button>

    </form>

</section>

<?php

include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';

<?php 
include __DIR__ .'/../includes/head.php';
include __DIR__.'/../../../config/database.php';
?>

<?php
include __DIR__ . '/../../views/includes/footer.php';
?>