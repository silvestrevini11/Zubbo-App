<?php
session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_user = (int) $_SESSION['usuario']['id'];

$stmt = $conn->prepare("
    SELECT nome_user, email_user, tel_user, date_user
    FROM Usuario
    WHERE id_user = ?
    LIMIT 1
");
$stmt->execute([$id_user]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$stmtEsportes = $conn->prepare("
    SELECT e.nome_esporte
    FROM Esporte e
    INNER JOIN Usuario_Esporte ue ON ue.id_esporte = e.id_esporte
    WHERE ue.id_user = ?
    ORDER BY e.nome_esporte
");
$stmtEsportes->execute([$id_user]);
$esportesUsuario = $stmtEsportes->fetchAll(PDO::FETCH_COLUMN);

$mensagensSucesso = [
    'nome' => 'Nome alterado com sucesso.',
    'email' => 'E-mail alterado com sucesso.',
    'telefone' => 'Telefone alterado com sucesso.',
    'senha' => 'Senha alterada com sucesso.',
    'data' => 'Data de nascimento alterada com sucesso.',
    'esportes' => 'Esportes alterados com sucesso.',
];

$mensagensErro = [
    'csrf' => 'Sua sessão expirou. Atualize a página e tente novamente.',
    'nome' => 'Informe um nome válido entre 3 e 50 caracteres.',
    'email' => 'Informe um e-mail válido.',
    'email_existente' => 'Este e-mail já está sendo usado por outra conta.',
    'telefone' => 'Informe um telefone com 11 números, incluindo o DDD.',
    'senha' => 'A nova senha precisa ter pelo menos 6 caracteres.',
    'senhas_diferentes' => 'A confirmação da senha não corresponde à nova senha.',
    'data' => 'Informe uma data de nascimento válida.',
    'salvar' => 'Não foi possível salvar a alteração. Tente novamente.',
    'esportes' => 'Selecione pelo menos um esporte.',
];

$sucesso = isset($_GET['sucesso']) ? ($mensagensSucesso[$_GET['sucesso']] ?? null) : null;
$erro = isset($_GET['erro']) ? ($mensagensErro[$_GET['erro']] ?? 'Não foi possível realizar a alteração.') : null;

include __DIR__ . '/../includes/head.php';
?>

<main class="editar-dados-container">
    <header class="editar-dados-topo">
        <a class="editar-dados-voltar" href="perfil.php" aria-label="Voltar para o perfil">←</a>
        <img class="editar-dados-logo" src="/../-TCC-/public/imagem/LogooZ.png" alt="Zubbo">
    </header>

    <h1>Alterar Dados</h1>
    <p class="editar-dados-subtitulo">Atualize somente a informação que desejar</p>

    <?php if ($sucesso): ?>
        <div class="editar-dados-alerta editar-dados-sucesso" role="status">
            <?= htmlspecialchars($sucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="editar-dados-alerta editar-dados-erro" role="alert">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <section class="editar-dados-lista" aria-label="Dados da conta">
        <form class="editar-dados-form" action="atualizar-dados.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="acao" value="nome">

            <label for="editar-nome">Nome completo</label>
            <input
                id="editar-nome"
                type="text"
                name="nome"
                maxlength="50"
                value="<?= htmlspecialchars($usuario['nome_user']) ?>"
                autocomplete="name"
                required
            >
            <button class="editar-campo-btn" type="submit">Alterar nome</button>
        </form>

        <form class="editar-dados-form" action="atualizar-dados.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="acao" value="email">

            <label for="editar-email">E-mail</label>
            <input
                id="editar-email"
                type="email"
                name="email"
                maxlength="70"
                value="<?= htmlspecialchars($usuario['email_user']) ?>"
                autocomplete="email"
                required
            >
            <small>Usaremos para recuperação de conta e notificações.</small>
            <button class="editar-campo-btn" type="submit">Alterar e-mail</button>
        </form>

        <form class="editar-dados-form" action="atualizar-dados.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="acao" value="telefone">

            <label for="editar-telefone">Telefone</label>
            <div class="editar-telefone-wrap">
                <span>+55</span>
                <input
                    id="editar-telefone"
                    type="tel"
                    name="telefone"
                    inputmode="numeric"
                    maxlength="11"
                    pattern="[0-9]{11}"
                    value="<?= htmlspecialchars(preg_replace('/\D/', '', $usuario['tel_user'])) ?>"
                    autocomplete="tel-national"
                    required
                >
            </div>
            <small>Digite DDD + número, usando somente números.</small>
            <button class="editar-campo-btn" type="submit">Alterar telefone</button>
        </form>

        <form class="editar-dados-form" action="atualizar-dados.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="acao" value="senha">

            <label for="editar-senha">Nova senha</label>
            <input
                id="editar-senha"
                type="password"
                name="senha"
                minlength="6"
                autocomplete="new-password"
                placeholder="Nova senha"
                required
            >

            <label class="editar-label-secundario" for="editar-confirmar-senha">Confirmar nova senha</label>
            <input
                id="editar-confirmar-senha"
                type="password"
                name="confirmar_senha"
                minlength="6"
                autocomplete="new-password"
                placeholder="Confirmar nova senha"
                required
            >
            <button class="editar-campo-btn" type="submit">Alterar senha</button>
        </form>

        <form class="editar-dados-form" action="atualizar-dados.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="acao" value="data">

            <label for="editar-data">Data de nascimento</label>
            <input
                id="editar-data"
                type="date"
                name="data_nascimento"
                value="<?= htmlspecialchars($usuario['date_user']) ?>"
                max="<?= date('Y-m-d') ?>"
                required
            >
            <button class="editar-campo-btn" type="submit">Alterar data de nascimento</button>
        </form>

        <section class="editar-dados-form editar-esportes-resumo" aria-labelledby="editar-esportes-titulo">
            <div class="editar-esportes-cabecalho">
                <div>
                    <h2 id="editar-esportes-titulo">Meus esportes</h2>
                    <p>
                        <?= !empty($esportesUsuario)
                            ? htmlspecialchars(implode(' • ', $esportesUsuario))
                            : 'Nenhum esporte selecionado' ?>
                    </p>
                </div>
            </div>
            <a class="editar-campo-btn editar-esportes-link" href="perfil-esportes-editar.php">Alterar esportes</a>
        </section>
    </section>

    <div class="editar-dados-seguranca">
        <span aria-hidden="true">✓</span>
        <p>Cada botão altera somente o campo correspondente.</p>
    </div>
</main>

<script>
    const telefoneEditar = document.getElementById('editar-telefone');
    if (telefoneEditar) {
        telefoneEditar.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
        });
    }
</script>

<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
