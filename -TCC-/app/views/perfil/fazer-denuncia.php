<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$idUsuario = (int) $_SESSION['usuario']['id'];
$erro = '';
$sucesso = isset($_GET['sucesso']);

if (empty($_SESSION['csrf_suporte'])) {
    $_SESSION['csrf_suporte'] = bin2hex(random_bytes(32));
}

$motivosPermitidos = [
    'Comportamento inadequado',
    'Assédio ou ofensa',
    'Evento irregular',
    'Conteúdo impróprio',
    'Spam ou fraude',
    'Outro',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $motivo = trim((string) ($_POST['motivo'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));

    if (!is_string($csrf) || !hash_equals($_SESSION['csrf_suporte'], $csrf)) {
        http_response_code(403);
        $erro = 'A sessão do formulário expirou. Atualize a página e tente novamente.';
    } elseif (!in_array($motivo, $motivosPermitidos, true)) {
        $erro = 'Selecione um motivo válido para a denúncia.';
    } elseif (mb_strlen($descricao) < 10 || mb_strlen($descricao) > 2000) {
        $erro = 'Descreva a situação usando entre 10 e 2000 caracteres.';
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO Denuncia (id_denunciante, motivo, descricao)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$idUsuario, $motivo, $descricao]);

            header('Location: fazer-denuncia.php?sucesso=1', true, 303);
            exit;
        } catch (PDOException $e) {
            error_log('Erro ao enviar denúncia: ' . $e->getMessage());
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
        <p class="suporte-mensagem suporte-mensagem-erro" role="alert">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <section class="suporte-form-card">
        <p>Explique o que aconteceu para que a administração possa analisar a situação.</p>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_suporte'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="suporte-campo">
                <label for="motivo">Motivo</label>
                <select id="motivo" name="motivo" required>
                    <option value="">Selecione um motivo</option>
                    <?php foreach ($motivosPermitidos as $opcao): ?>
                        <option
                            value="<?= htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($_POST['motivo'] ?? '') === $opcao ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="suporte-campo">
                <label for="descricao">Descrição</label>
                <textarea
                    id="descricao"
                    name="descricao"
                    minlength="10"
                    maxlength="2000"
                    placeholder="Conte o que aconteceu e inclua as informações necessárias para a administração entender a situação."
                    required
                ><?= htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <button class="suporte-form-botao" type="submit">Enviar denúncia</button>
        </form>

        <small class="suporte-form-ajuda">
            A denúncia será salva como pendente na tabela Denuncia e aparecerá em Painel ADM → Denúncias.
        </small>
    </section>
</main>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
