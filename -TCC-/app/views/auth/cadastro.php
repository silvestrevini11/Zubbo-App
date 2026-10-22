<?php
require_once __DIR__ . '/../../../config/security.php';
zubbo_start_session();

$erroCodigo = (string) ($_GET['erro'] ?? '');
$mensagens = [
    'dados' => 'Confira os dados informados. A senha deve ter pelo menos 8 caracteres.',
    'email' => 'Este e-mail já está cadastrado.',
    'email_envio' => 'Não foi possível enviar o código de verificação. Confira a configuração de e-mail e tente novamente.',
    'limite' => 'Muitas tentativas de cadastro. Aguarde antes de tentar novamente.',
];
$erro = $mensagens[$erroCodigo] ?? '';

include __DIR__ . '/../includes/head.php';
?>
<section class="Tela-cad">
    <button class="btn-voltar" type="button" onclick="window.location.href='<?= htmlspecialchars(zubbo_url('/public/index.php'), ENT_QUOTES, 'UTF-8') ?>'">←</button>
    <img class="logo-cad" src="<?= htmlspecialchars(zubbo_url('/public/imagem/LogooZ.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Zubbo">

    <h1 class="title-cad">Criar Conta</h1>
    <p class="desc-cad">Junte-se à comunidade e viva o esporte</p>

    <?php if ($erro !== ''): ?>
        <div class="alert erro-esqueci-senha" role="alert">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form class="Cadastro-tabela" action="insert-user.php" method="post">
        <?= zubbo_csrf_input() ?>
        <div class="field-cad">
            <input type="text" placeholder="Nome Completo" name="name-txt" maxlength="100" required>
        </div>

        <div class="field-cad">
            <input type="email" placeholder="E-mail" name="email-txt" autocomplete="email" required>
        </div>

        <p class="par-cad">Usaremos para recuperação de conta e notificações</p>

        <div class="field-cad telefone-container">
            <span class="codigo-pais">+55</span>
            <input type="tel" placeholder="Telefone" inputmode="numeric" name="telefone-tel"
                   id="telefone" maxlength="11" pattern="[0-9]{11}" required>
        </div>

        <div class="field-cad">
            <input type="password" placeholder="Senha" name="Senha-pass" id="senha"
                   minlength="8" maxlength="255" autocomplete="new-password" required>
        </div>

        <div class="field-cad">
            <input type="password" placeholder="Confirmar Senha" name="confirmar-senha"
                   id="confirmar-senha" minlength="8" maxlength="255"
                   autocomplete="new-password" required>
        </div>

        <p id="erro-senha" style="display:none;">As senhas não coincidem.</p>

        <h2 class="sub-title-cad">Data de nascimento</h2>
        <div class="field-cad">
            <input type="date" name="data-nasc" required>
        </div>

        <p class="par-cad">Antes de continuar, conheça como a versão de demonstração trata dados em <a href="<?= htmlspecialchars(zubbo_url('/public/privacidade.php'), ENT_QUOTES, 'UTF-8') ?>">Privacidade e uso responsável</a>.</p>
        <button class="btn-cad" type="submit">Criar conta</button>
    </form>

    <h3 class="enter-cad">Já tem uma conta? <a href="login.php">Entrar</a></h3>
</section>
<script src="<?= htmlspecialchars(zubbo_url('/public/js/cadastro.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
