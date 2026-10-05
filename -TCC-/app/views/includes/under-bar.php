<?php require_once __DIR__ . '/../../../config/security.php'; ?>
<nav class="bottom-nav">
    <a href="<?= htmlspecialchars(zubbo_url('/app/views/painel/painel-inicial.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <img class="nav-icon" src="<?= htmlspecialchars(zubbo_url('/public/imagem/inicio.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Início">
        <span>Início</span>
    </a>

    <a href="<?= htmlspecialchars(zubbo_url('/app/views/pesquisa/pesquisar.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <img class="nav-icon" src="<?= htmlspecialchars(zubbo_url('/public/imagem/pesquisa.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Pesquisa">
        <span>Explorar</span>
    </a>

    <a href="<?= htmlspecialchars(zubbo_url('/app/views/eventos/criar-evento.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-add" aria-label="Criar evento">
        <span>+</span>
    </a>

    <a href="<?= htmlspecialchars(zubbo_url('/app/views/chats/chats.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <img class="nav-icon" src="<?= htmlspecialchars(zubbo_url('/public/imagem/chat.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Chat">
        <span>Mensagens</span>
    </a>

    <a href="<?= htmlspecialchars(zubbo_url('/app/views/perfil/perfil.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <img class="nav-icon" src="<?= htmlspecialchars(zubbo_url('/public/imagem/perfil.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Perfil">
        <span>Perfil</span>
    </a>
</nav>
