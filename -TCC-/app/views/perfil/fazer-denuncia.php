<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (empty($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/logger.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$erro = '';
$sucesso = isset($_GET['sucesso']);
$motivosPermitidos = [
    'Comportamento inadequado', 'Assédio ou ofensa', 'Evento irregular',
    'Conteúdo impróprio', 'Spam ou fraude', 'Outro',
];
$tiposPermitidos = ['nenhum', 'usuario', 'evento', 'local'];
$tipoAlvo = (string) ($_POST['alvo_tipo'] ?? 'nenhum');

$usuarios = $conn->prepare("SELECT id_user, nome_user FROM Usuario WHERE status_user = 'ativo' AND id_user <> ? ORDER BY nome_user LIMIT 300");
$usuarios->execute([$idUsuario]);
$usuarios = $usuarios->fetchAll(PDO::FETCH_ASSOC);
$eventos = $conn->query("SELECT id_evento, nome_evento FROM Evento WHERE status_evento = 'ativo' ORDER BY data_evento DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
$locais = $conn->query("SELECT id_local, nome_local, endereco_local FROM LocalEsp WHERE status_local = 'aprovado' ORDER BY nome_local LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $motivo = trim((string) ($_POST['motivo'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));

    $idAlvo = null;
    $campoId = [
        'usuario' => 'id_denunciado',
        'evento' => 'id_evento',
        'local' => 'id_local',
    ];

    if (!is_string($csrf) || !hash_equals(zubbo_csrf_token(), $csrf)) {
        http_response_code(403);
        $erro = 'A sessão do formulário expirou. Atualize a página e tente novamente.';
    } elseif (!in_array($tipoAlvo, $tiposPermitidos, true)) {
        $erro = 'Selecione um tipo de denúncia válido.';
    } elseif (!in_array($motivo, $motivosPermitidos, true)) {
        $erro = 'Selecione um motivo válido para a denúncia.';
    } elseif (mb_strlen($descricao) < 10 || mb_strlen($descricao) > 2000) {
        $erro = 'Descreva a situação usando entre 10 e 2000 caracteres.';
    } else {
        try {
            if ($tipoAlvo !== 'nenhum') {
                $idAlvo = filter_var($_POST['alvo_id_' . $tipoAlvo] ?? null, FILTER_VALIDATE_INT);
                if ($idAlvo === false || $idAlvo === null || $idAlvo <= 0) {
                    throw new InvalidArgumentException('Selecione quem ou o que deseja denunciar.');
                }

                $consultas = [
                    'usuario' => "SELECT 1 FROM Usuario WHERE id_user = ? AND status_user = 'ativo' AND id_user <> ?",
                    'evento' => "SELECT 1 FROM Evento WHERE id_evento = ? AND status_evento = 'ativo'",
                    'local' => "SELECT 1 FROM LocalEsp WHERE id_local = ? AND status_local = 'aprovado'",
                ];
                $stmtAlvo = $conn->prepare($consultas[$tipoAlvo]);
                $stmtAlvo->execute($tipoAlvo === 'usuario' ? [$idAlvo, $idUsuario] : [$idAlvo]);
                if (!$stmtAlvo->fetchColumn()) {
                    throw new InvalidArgumentException('O alvo escolhido não está disponível.');
                }

                if ($tipoAlvo === 'local') {
                    $stmtColuna = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Denuncia' AND COLUMN_NAME = 'id_local'");
                    if (!(bool) $stmtColuna->fetchColumn()) {
                        throw new InvalidArgumentException('A opção de denunciar locais ainda está sendo configurada. Tente novamente depois.');
                    }
                }
            }

            $colunas = ['id_denunciante', 'motivo', 'descricao'];
            $valores = [$idUsuario, $motivo, $descricao];
            if ($idAlvo !== null) {
                $colunas[] = $campoId[$tipoAlvo];
                $valores[] = $idAlvo;
            }
            $marcadores = implode(', ', array_fill(0, count($colunas), '?'));
            $sql = 'INSERT INTO Denuncia (' . implode(', ', $colunas) . ') VALUES (' . $marcadores . ')';
            $stmt = $conn->prepare($sql);
            $stmt->execute($valores);
            zubbo_log('info', 'report.created', ['user_id' => $idUsuario, 'report_id' => (int) $conn->lastInsertId(), 'entity_type' => $tipoAlvo]);

            header('Location: fazer-denuncia.php?sucesso=1', true, 303);
            exit;
        } catch (InvalidArgumentException $e) {
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            zubbo_log('error', 'report.create_failed', ['user_id' => $idUsuario, 'reason_code' => 'db_failure']);
            $erro = 'Não foi possível enviar sua denúncia agora. Tente novamente.';
        }
    }
}

include __DIR__ . '/../includes/head.php';
?>
<main class="suporte-form-container">
    <header class="suporte-form-cabecalho">
        <a class="suporte-form-voltar" href="perfil-configuracoes.php" aria-label="Voltar">←</a>
        <div>
            <p class="suporte-form-legenda">SUPORTE</p>
            <h1>Fazer denúncia</h1>
        </div>
    </header>

    <?php if ($sucesso): ?>
        <p class="suporte-mensagem suporte-mensagem-sucesso" role="status">
            Denúncia enviada! Ela já está disponível na central de denúncias do painel administrativo.
        </p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p class="suporte-mensagem suporte-mensagem-erro" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <section class="suporte-form-card">
        <p>Explique o que aconteceu para que a administração possa analisar a situação. Você pode indicar uma pessoa, evento, local ou enviar uma denúncia geral.</p>
        <form method="post">
            <?= zubbo_csrf_input() ?>

            <div class="suporte-campo">
                <label for="alvo_tipo">O que você deseja denunciar?</label>
                <select id="alvo_tipo" name="alvo_tipo" required>
                    <option value="nenhum" <?= $tipoAlvo === 'nenhum' ? 'selected' : '' ?>>Denúncia geral (sem alvo específico)</option>
                    <option value="usuario" <?= $tipoAlvo === 'usuario' ? 'selected' : '' ?>>Um usuário</option>
                    <option value="evento" <?= $tipoAlvo === 'evento' ? 'selected' : '' ?>>Um evento</option>
                    <option value="local" <?= $tipoAlvo === 'local' ? 'selected' : '' ?>>Um local esportivo</option>
                </select>
            </div>

            <div class="suporte-campo suporte-alvo-campo" data-alvo="usuario" hidden>
                <label for="alvo_id_usuario">Usuário denunciado</label>
                <select id="alvo_id_usuario" name="alvo_id_usuario" disabled required>
                    <option value="">Selecione o usuário</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int)$u['id_user'] ?>" <?= (int)($_POST['alvo_id_usuario'] ?? 0) === (int)$u['id_user'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nome_user'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="suporte-campo suporte-alvo-campo" data-alvo="evento" hidden>
                <label for="alvo_id_evento">Evento denunciado</label>
                <select id="alvo_id_evento" name="alvo_id_evento" disabled required>
                    <option value="">Selecione o evento</option>
                    <?php foreach ($eventos as $ev): ?>
                        <option value="<?= (int)$ev['id_evento'] ?>" <?= (int)($_POST['alvo_id_evento'] ?? 0) === (int)$ev['id_evento'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['nome_evento'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="suporte-campo suporte-alvo-campo" data-alvo="local" hidden>
                <label for="alvo_id_local">Local denunciado</label>
                <select id="alvo_id_local" name="alvo_id_local" disabled required>
                    <option value="">Selecione o local</option>
                    <?php foreach ($locais as $local): ?>
                        <option value="<?= (int)$local['id_local'] ?>" <?= (int)($_POST['alvo_id_local'] ?? 0) === (int)$local['id_local'] ? 'selected' : '' ?>><?= htmlspecialchars($local['nome_local'] . ' — ' . $local['endereco_local'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="suporte-campo">
                <label for="motivo">Motivo</label>
                <select id="motivo" name="motivo" required>
                    <option value="">Selecione um motivo</option>
                    <?php foreach ($motivosPermitidos as $opcao): ?>
                        <option value="<?= htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8') ?>" <?= ($_POST['motivo'] ?? '') === $opcao ? 'selected' : '' ?>><?= htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="suporte-campo">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" minlength="10" maxlength="2000"
                    placeholder="Conte o que aconteceu e inclua as informações necessárias para a administração entender a situação."
                    required><?= htmlspecialchars((string) ($_POST['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <button class="suporte-form-botao" type="submit">Enviar denúncia</button>
        </form>
        <small class="suporte-form-ajuda">Sua denúncia será enviada à central de moderação para análise.</small>
    </section>
</main>
<script>
(() => {
    const tipo = document.getElementById('alvo_tipo');
    const grupos = document.querySelectorAll('.suporte-alvo-campo');
    function atualizar() {
        for (const grupo of grupos) {
            const mostrar = grupo.dataset.alvo === tipo.value;
            grupo.hidden = !mostrar;
            grupo.querySelector('select').disabled = !mostrar;
        }
    }
    tipo.addEventListener('change', atualizar);
    atualizar();
})();
</script>
<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
