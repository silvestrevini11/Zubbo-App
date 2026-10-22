<?php require_once __DIR__ . '/../../../config/security.php'; ?>
<nav class="bottom-nav">
    <a href="<?= htmlspecialchars(zubbo_url('/app/views/painel/Painel-inicial.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <img class="nav-icon" src="<?= htmlspecialchars(zubbo_url('/public/imagem/inicio.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Início">
        <span class="nav-unread-dot" id="nav-inicio-notificacoes" hidden aria-label="Notificações não lidas"></span>
        <span>Início</span>
    </a>

    <a href="<?= htmlspecialchars(zubbo_url('/app/views/pesquisa/pesquisar.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <img class="nav-icon" src="<?= htmlspecialchars(zubbo_url('/public/imagem/pesquisa.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Pesquisa">
        <span>Explorar</span>
    </a>

    <a href="<?= htmlspecialchars(zubbo_url('/app/views/eventos/criar-evento.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-add" aria-label="Criar evento">
        <span>+</span>
    </a>

    <a href="<?= htmlspecialchars(zubbo_url('/app/views/chats/chats.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item nav-mensagens">
        <img class="nav-icon" src="<?= htmlspecialchars(zubbo_url('/public/imagem/chat.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Chat">
        <span class="nav-unread-badge" id="nav-conversas-nao-lidas" hidden aria-label="Conversas com mensagens não lidas"></span>
        <span>Mensagens</span>
    </a>

    <a href="<?= htmlspecialchars(zubbo_url('/app/views/perfil/perfil.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
        <img class="nav-icon" src="<?= htmlspecialchars(zubbo_url('/public/imagem/perfil.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Perfil">
        <span>Perfil</span>
    </a>
</nav>

<script>
(() => {
    const badge = document.getElementById('nav-conversas-nao-lidas');
    const inicio = document.getElementById('nav-inicio-notificacoes');
    if (!badge) return;
    const url = <?= json_encode(zubbo_url('/app/views/notificacoes/buscar-notificacoes.php')) ?>;
    let totalAnterior = null;
    async function carregarNaoLidas() {
        try {
            const resposta = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
            if (!resposta.ok) return;
            const dados = await resposta.json();
            const quantidade = Math.max(0, Number(dados.conversas_nao_lidas) || 0);
            badge.hidden = quantidade === 0;
            badge.textContent = quantidade > 99 ? '99+' : String(quantidade);
            if (inicio) inicio.hidden = (Number(dados.quantidade) || 0) <= 0;
            const total = Math.max(0, Number(dados.quantidade) || 0);
            if (totalAnterior !== null && total > totalAnterior &&
                localStorage.getItem('zubbo-alertas') === 'sim' &&
                'Notification' in window && Notification.permission === 'granted' &&
                document.hidden) {
                try {
                    new Notification('Zubbo: novas notificações', {
                        body: 'Você recebeu novas atualizações no aplicativo.',
                        tag: 'zubbo-novas-notificacoes'
                    });
                } catch (e) { /* Alguns navegadores móveis exigem service worker. */ }
            }
            totalAnterior = total;
        } catch (erro) {
            console.warn('Não foi possível atualizar mensagens não lidas.');
        }
    }
    carregarNaoLidas();
    setInterval(carregarNaoLidas, 15000);
})();
</script>
