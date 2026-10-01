<?php
session_start();
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';
$idUsuario = (int) $_SESSION['usuario']['id'];
$idEvento = filter_var($_GET['id_evento'] ?? null, FILTER_VALIDATE_INT);
if (!$idEvento || $idEvento < 1) {
    http_response_code(404);
    exit('Evento não encontrado.');
}
if (empty($_SESSION['csrf_eventos'])) {
    $_SESSION['csrf_eventos'] = bin2hex(random_bytes(32));
}
function escaparDetalhe($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
function carregarEvento($conn, $id) {
    $stmt = $conn->prepare("
        SELECT ev.*, e.nome_esporte, l.nome_local, l.endereco_local,
               u.nome_user AS criador,
               (TIMESTAMP(ev.data_evento, ev.horario_evento) >= NOW()) AS aberto
        FROM Evento ev
        INNER JOIN Esporte e ON e.id_esporte = ev.id_esporte
        INNER JOIN LocalEsp l ON l.id_local = ev.id_local
        LEFT JOIN Usuario u ON u.id_user = ev.id_criador
        WHERE ev.id_evento = ? AND ev.status_evento <> 'removido'
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
try {
    $evento = carregarEvento($conn, $idEvento);
} catch (PDOException $e) {
    error_log('Erro ao abrir evento: ' . $e->getMessage());
    http_response_code(503);
    exit('Não foi possível carregar o evento. Tente novamente.');
}
if (!$evento) {
    http_response_code(404);
    exit('Evento não encontrado.');
}
$organizador = (int) $evento['id_criador'] === $idUsuario;
$erro = '';
$sucesso = $_SESSION['flash_evento'][$idEvento] ?? '';
unset($_SESSION['flash_evento'][$idEvento]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    $acao = $_POST['acao'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['csrf_eventos'], $csrf)) {
        http_response_code(403);
        $erro = 'A sessão do formulário expirou. Atualize a página.';
    } elseif (!in_array($acao, ['participar', 'sair', 'adicionar'], true)) {
        http_response_code(400);
        $erro = 'Ação inválida.';
    } else {
        try {
            $conn->beginTransaction();
            // Serializa inscrições no mesmo evento e verifica novamente seu estado.
            $lock = $conn->prepare("
                SELECT id_criador, status_evento,
                       (TIMESTAMP(data_evento, horario_evento) >= NOW()) AS aberto
                FROM Evento WHERE id_evento = ? FOR UPDATE
            ");
            $lock->execute([$idEvento]);
            $estado = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$estado || $estado['status_evento'] !== 'ativo' || !$estado['aberto']) {
                throw new RuntimeException('As inscrições para este evento estão encerradas.');
            }
            if ($acao === 'sair') {
                $stmt = $conn->prepare('DELETE FROM Lista_Evento WHERE id_evento = ? AND id_user = ?');
                $stmt->execute([$idEvento, $idUsuario]);
                $mensagem = 'Sua participação foi cancelada.';
            } else {
                if ($acao === 'adicionar') {
                    if ((int) $estado['id_criador'] !== $idUsuario) {
                        throw new RuntimeException('Somente o organizador pode adicionar integrantes.');
                    }
                    $selecionados = $_POST['integrantes'] ?? [];
                    if (!is_array($selecionados) || !$selecionados || count($selecionados) > 100) {
                        throw new RuntimeException('Selecione entre 1 e 100 integrantes.');
                    }
                } else {
                    $selecionados = [$idUsuario];
                }
                $ids = [];
                foreach ($selecionados as $valor) {
                    $id = filter_var($valor, FILTER_VALIDATE_INT);
                    if (!$id || $id < 1) {
                        throw new RuntimeException('Integrante inválido.');
                    }
                    $ids[$id] = $id;
                }
                $validar = $conn->prepare("SELECT id_user FROM Usuario WHERE id_user = ? AND status_user = 'ativo'");
                $existe = $conn->prepare('SELECT 1 FROM Lista_Evento WHERE id_evento = ? AND id_user = ?');
                $inserir = $conn->prepare('INSERT INTO Lista_Evento (id_user, id_evento) VALUES (?, ?)');
                $novos = 0;
                foreach ($ids as $id) {
                    $validar->execute([$id]);
                    if (!$validar->fetchColumn()) {
                        throw new RuntimeException('Um dos integrantes não possui uma conta ativa.');
                    }
                    $existe->execute([$idEvento, $id]);
                    if (!$existe->fetchColumn()) {
                        $inserir->execute([$id, $idEvento]);
                        $novos++;
                    }
                }
                $mensagem = $acao === 'participar'
                    ? 'Sua participação está confirmada!'
                    : ($novos . ' integrante(s) adicionado(s).');
            }
            $conn->commit();
            $_SESSION['flash_evento'][$idEvento] = $mensagem;
            header('Location: detalhes-evento.php?id_evento=' . $idEvento, true, 303);
            exit;
        } catch (RuntimeException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $erro = $e instanceof PDOException
                ? 'Não foi possível salvar a participação. Tente novamente.'
                : $e->getMessage();
            if ($e instanceof PDOException) error_log('Erro de participação: ' . $e->getMessage());
        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log('Erro de participação: ' . $e->getMessage());
            $erro = 'Não foi possível salvar a participação. Tente novamente.';
        }
    }
}
$participantes = [];
$disponiveis = [];
$erroLista = false;
try {
    $stmt = $conn->prepare("
        SELECT u.id_user, u.nome_user FROM Lista_Evento le
        INNER JOIN Usuario u ON u.id_user = le.id_user
        WHERE le.id_evento = ? ORDER BY u.nome_user, u.id_user
    ");
    $stmt->execute([$idEvento]);
    $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($organizador) {
        $stmt = $conn->prepare("
            SELECT u.id_user, u.nome_user FROM Usuario u
            WHERE u.status_user = 'ativo'
              AND NOT EXISTS (
                SELECT 1 FROM Lista_Evento le
                WHERE le.id_evento = ? AND le.id_user = u.id_user
              )
            ORDER BY u.nome_user, u.id_user
        ");
        $stmt->execute([$idEvento]);
        $disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Erro ao listar participantes: ' . $e->getMessage());
    $erroLista = true;
}
$inscrito = in_array($idUsuario, array_map('intval', array_column($participantes, 'id_user')), true);
$aberto = $evento['status_evento'] === 'ativo' && (bool) $evento['aberto'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escaparDetalhe($evento['nome_evento']) ?> | Zubbo</title>
    <script>if (localStorage.getItem('zubbo-tema') === 'escuro') document.documentElement.classList.add('tema-escuro');</script>
    <link rel="stylesheet" href="../../../public/css/style.css">
    <link rel="stylesheet" href="../../../public/css/eventos.css">
</head>
<body>
<main class="eventos-container">
    <a class="eventos-voltar" href="eventos.php">← Voltar aos eventos</a>
    <header class="eventos-topo">
        <div>
            <p class="eventos-marca"><?= escaparDetalhe($evento['nome_esporte']) ?></p>
            <h1><?= escaparDetalhe($evento['nome_evento']) ?></h1>
        </div>
    </header>
    <?php if ($sucesso): ?><p class="evento-aviso evento-aviso-sucesso" role="status"><?= escaparDetalhe($sucesso) ?></p><?php endif; ?>
    <?php if ($erro): ?><p class="evento-aviso" role="alert"><?= escaparDetalhe($erro) ?></p><?php endif; ?>
    <section class="eventos-card evento-detalhes">
        <h2>Sobre o encontro</h2>
        <dl class="eventos-dados">
            <div><dt>Data e horário</dt><dd><?= escaparDetalhe(date('d/m/Y', strtotime($evento['data_evento']))) ?> às <?= escaparDetalhe(substr($evento['horario_evento'], 0, 5)) ?></dd></div>
            <div><dt>Local</dt><dd><?= escaparDetalhe($evento['nome_local']) ?></dd><dd class="eventos-endereco"><?= escaparDetalhe($evento['endereco_local']) ?></dd></div>
            <div><dt>Organizador</dt><dd><?= escaparDetalhe($evento['criador'] ?? 'Usuário indisponível') ?></dd></div>
            <div><dt>Status</dt><dd><?= $evento['status_evento'] === 'cancelado' ? 'Cancelado' : ($aberto ? 'Inscrições abertas' : 'Inscrições encerradas') ?></dd></div>
        </dl>
        <?php if ($aberto && !$erroLista): ?>
            <form method="post" class="evento-participar">
                <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                <input type="hidden" name="acao" value="<?= $inscrito ? 'sair' : 'participar' ?>">
                <button class="eventos-criar" type="submit"><?= $inscrito ? 'Cancelar minha participação' : 'Quero participar' ?></button>
            </form>
        <?php endif; ?>
    </section>
    <section class="eventos-card evento-detalhes">
        <h2>Integrantes<?= !$erroLista ? ' (' . count($participantes) . ')' : '' ?></h2>
        <?php if ($erroLista): ?><p role="alert">Não foi possível carregar os integrantes. Atualize a página.</p>
        <?php elseif (!$participantes): ?><p>Ninguém confirmou presença ainda.</p>
        <?php else: ?>
            <ul class="evento-integrantes">
                <?php foreach ($participantes as $p): ?><li><?= escaparDetalhe($p['nome_user']) ?><?= (int) $p['id_user'] === $idUsuario ? ' (você)' : '' ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <?php if ($organizador && $aberto && !$erroLista): ?>
        <section class="eventos-card evento-detalhes">
            <h2>Adicionar integrantes</h2>
            <p class="eventos-subtitulo">Selecione pessoas cadastradas no app para este encontro.</p>
            <?php if (!$disponiveis): ?><p>Todos os usuários ativos já estão inscritos.</p>
            <?php else: ?>
                <form method="post" class="evento-adicionar">
                    <input type="hidden" name="csrf" value="<?= escaparDetalhe($_SESSION['csrf_eventos']) ?>">
                    <input type="hidden" name="acao" value="adicionar">
                    <fieldset><legend>Quem vai participar?</legend>
                    <div class="evento-selecao">
                        <?php foreach ($disponiveis as $p): ?>
                        <label><input type="checkbox" name="integrantes[]" value="<?= (int) $p['id_user'] ?>"> <span><?= escaparDetalhe($p['nome_user']) ?> <small>#<?= (int) $p['id_user'] ?></small></span></label>
                        <?php endforeach; ?>
                    </div>
                    </fieldset>
                    <button class="eventos-criar" type="submit">Cadastrar selecionados</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/under-bar.php'; require __DIR__ . '/../includes/footer.php'; ?>
