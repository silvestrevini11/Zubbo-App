(() => {
    document.querySelectorAll('[data-admin-usuario]').forEach(form => {
        const acao = form.elements.namedItem('acao');
        const motivo = form.elements.namedItem('motivo');
        const atualizarMotivo = () => {
            const promocao = acao.value === 'tornar_admin';
            const texto = promocao ? 'Motivo da promoção' : 'Motivo';
            motivo.placeholder = texto;
            motivo.setAttribute('aria-label', `${texto} para ${form.dataset.usuarioNome}`);
            if (promocao) motivo.minLength = 8;
            else motivo.removeAttribute('minlength');
        };
        acao.addEventListener('change', atualizarMotivo);
        form.addEventListener('reset', () => queueMicrotask(atualizarMotivo));
        window.addEventListener('pageshow', atualizarMotivo);
        form.addEventListener('submit', event => {
            if (acao.value === 'banido'
                && !window.confirm('Banir este usuário e remover suas participações, mensagens e registros associados? Reativar a conta não restaura esses dados.')) {
                event.preventDefault();
                return;
            }
            if (acao.value === 'tornar_admin'
                && !window.confirm('Conceder acesso administrativo completo a este usuário?')) {
                event.preventDefault();
            }
        });
        atualizarMotivo();
    });
})();
