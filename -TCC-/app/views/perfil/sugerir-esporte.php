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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $nomeEsporte = trim((string) ($_POST['nome_esporte'] ?? ''));

    if (!is_string($csrf) || !hash_equals($_SESSION['csrf_suporte'], $csrf)) {
        http_response_code(403);
        $erro = 'A sessão do formulário expirou. Atualize a página e tente novamente.';
    } elseif ($nomeEsporte === '' || mb_strlen($nomeEsporte) < 2 || mb_strlen($nomeEsporte) > 50) {
        $erro = 'Informe um esporte com 2 a 50 caracteres.';
    } else {
        try {
            $stmt = $conn->prepare('SELECT 1 FROM Esporte WHERE LOWER(nome_esporte) = LOWER(?) LIMIT 1');
            $stmt->execute([$nomeEsporte]);

            if ($stmt->fetchColumn()) {
                throw new RuntimeException('Esse esporte já está disponível no Zubbo.');
            }

            $stmt = $conn->prepare("
                SELECT 1
                FROM Sugestao_Esporte
                WHERE id_user = ?
                  AND LOWER(nome_esporte) = LOWER(?)
                  AND status_sugestao = 'pendente'
                LIMIT 1
            ");
            $stmt->execute([$idUsuario, $nomeEsporte]);

            if ($stmt->fetchColumn()) {
                throw new RuntimeException('Você já possui uma sugestão pendente para esse esporte.');
            }

            $stmt = $conn->prepare("
                INSERT INTO Sugestao_Esporte (nome_esporte, id_user)
                VALUES (?, ?)
            ");
            $stmt->execute([$nomeEsporte, $idUsuario]);

            header('Location: sugerir-esporte.php?sucesso=1', true, 303);
            exit;
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Erro ao enviar sugestão de esporte: ' . $e->getMessage());
            $erro = 'Não foi possível enviar sua sugestão agora. Tente novamente.';
        }
    }
}

include __DIR__ . '/../includes/head.php';
?>

<main class="suporte-form-container">
    <header class="suporte-form-cabecalho">
        <a class="suporte-form-voltar" href="perfil-configuracoes.php" aria-label="Voltar">←</a>
        <div>
            <p class="suporte-form-legenda">COMUNIDADE</p>
            <h1>Sugerir esporte</h1>
        </div>
    </header>

    <?php if ($sucesso): ?>
        <p class="suporte-mensagem suporte-mensagem-sucesso" role="status">
            Sugestão enviada! Ela já está disponível no painel administrativo para análise.
        </p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p class="suporte-mensagem suporte-mensagem-erro" role="alert">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <section class="suporte-form-card">
        <p>Sugira uma modalidade que ainda não aparece no aplicativo. A administração poderá aprovar ou rejeitar sua sugestão.</p>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_suporte'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="suporte-campo">
                <label for="nome_esporte">Nome do esporte</label>
                <input
                    id="nome_esporte"
                    name="nome_esporte"
                    type="text"
                    maxlength="50"
                    placeholder="Ex.: Tênis de mesa"
                    value="<?= htmlspecialchars($_POST['nome_esporte'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <button class="suporte-form-botao" type="submit">Enviar sugestão</button>
        </form>

        <small class="suporte-form-ajuda">
            A sugestão será salva como pendente na tabela Sugestao_Esporte e aparecerá em Painel ADM → Sugestões.
        </small>
    </section>
</main>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
