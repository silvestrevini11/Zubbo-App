<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include __DIR__ . '/../includes/head.php';
?>

<main class="configuracoes" style="padding-bottom: 96px;">
    <header class="configuracoes-cabecalho">
        <a class="configuracoes-voltar" href="perfil.php" aria-label="Voltar">←</a>
        <div>
            <p class="configuracoes-legenda">PREFERÊNCIAS</p>
            <h1>Configurações</h1>
        </div>
    </header>

    <?php if (isset($_GET['erro'])): ?>
        <p class="configuracoes-erro">
            <?= $_GET['erro'] === 'senha' ? 'A senha informada está incorreta.' : ($_GET['erro'] === 'admin' ? 'A conta administra o sistema. Revogue primeiro seu acesso administrativo.' : 'Não foi possível desativar a conta agora.') ?>
        </p>
    <?php endif; ?>

    <section class="configuracoes-grupo" aria-labelledby="aparencia-titulo">
        <h2 id="aparencia-titulo">Aparência</h2>
        <div class="configuracoes-item configuracoes-tema">
            <div class="configuracoes-icone" aria-hidden="true">◐</div>
            <div class="configuracoes-item-texto">
                <strong>Tema escuro</strong>
                <span>Use cores escuras no aplicativo</span>
            </div>
            <label class="tema-interruptor" for="tema-escuro">
                <input type="checkbox" id="tema-escuro" aria-label="Ativar tema escuro">
                <span></span>
            </label>
        </div>
    </section>

    <section class="configuracoes-grupo" aria-labelledby="suporte-titulo">
        <h2 id="suporte-titulo">Comunidade e suporte</h2>

        <a class="configuracoes-item configuracoes-link" href="sugerir-esporte.php">
            <span class="configuracoes-icone" aria-hidden="true">＋</span>
            <span class="configuracoes-item-texto">
                <strong>Sugerir esporte</strong>
                <span>Envie uma modalidade para análise da administração</span>
            </span>
            <span class="configuracoes-seta" aria-hidden="true">›</span>
        </a>

        <a class="configuracoes-item configuracoes-link configuracoes-link-separado" href="fazer-denuncia.php">
            <span class="configuracoes-icone" aria-hidden="true">!</span>
            <span class="configuracoes-item-texto">
                <strong>Fazer denúncia</strong>
                <span>Envie uma situação para a central de denúncias do administrador</span>
            </span>
            <span class="configuracoes-seta" aria-hidden="true">›</span>
        </a>
    </section>

    <section class="configuracoes-grupo" aria-labelledby="conta-titulo">
        <h2 id="conta-titulo">Conta</h2>
        <form action="../auth/logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button class="configuracoes-item configuracoes-botao" type="submit">
                <span class="configuracoes-icone" aria-hidden="true">↪</span>
                <span class="configuracoes-item-texto"><strong>Sair da conta</strong><span>Você poderá entrar novamente depois</span></span>
                <span class="configuracoes-seta" aria-hidden="true">›</span>
            </button>
        </form>
        <button class="configuracoes-item configuracoes-botao configuracoes-perigo" type="button" data-abrir-exclusao>
            <span class="configuracoes-icone" aria-hidden="true">×</span>
            <span class="configuracoes-item-texto"><strong>Desativar conta</strong><span>Remove os dados básicos e impede novo acesso.</span></span>
            <span class="configuracoes-seta" aria-hidden="true">›</span>
        </button>
    </section>

    <p class="configuracoes-versao"><a href="<?= htmlspecialchars(zubbo_url('/public/privacidade.php'), ENT_QUOTES, 'UTF-8') ?>">Privacidade e uso responsável</a> · Zubbo <span>versão 1.0.0</span></p>
</main>

<dialog class="modal-excluir" id="modal-excluir" aria-labelledby="titulo-excluir">
    <form action="excluir-conta.php" method="post" class="modal-excluir-conteudo">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <h2 id="titulo-excluir">Desativar e anonimizar sua conta?</h2>
        <p>Seu perfil será desativado e seus dados básicos serão anonimizados. Alguns registros associados a eventos, mensagens e moderação poderão ser conservados conforme a política de retenção. Para confirmar, informe sua senha.</p>
        <label for="senha-confirmacao">Senha</label>
        <input id="senha-confirmacao" type="password" name="senha" required autocomplete="current-password">
        <div class="modal-excluir-acoes">
            <button type="button" class="modal-cancelar" data-fechar-exclusao>Cancelar</button>
            <button type="submit" class="modal-confirmar">Confirmar desativação</button>
        </div>
    </form>
</dialog>

<script>
    const temaEscuro = document.getElementById('tema-escuro');
    const aplicarTema = (escuro) => {
        document.documentElement.classList.toggle('tema-escuro', escuro);
        localStorage.setItem('zubbo-tema', escuro ? 'escuro' : 'claro');
    };

    temaEscuro.checked = document.documentElement.classList.contains('tema-escuro');
    temaEscuro.addEventListener('change', () => aplicarTema(temaEscuro.checked));

    const modalExclusao = document.getElementById('modal-excluir');
    document.querySelector('[data-abrir-exclusao]').addEventListener('click', () => modalExclusao.showModal());
    document.querySelector('[data-fechar-exclusao]').addEventListener('click', () => modalExclusao.close());
</script>
<?php
include __DIR__ . '/../../views/includes/under-bar.php';
include __DIR__ . '/../../views/includes/footer.php';
?>
