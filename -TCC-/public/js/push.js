/* Push real via Service Worker; nenhuma permissão é solicitada sem clique do usuário. */
(() => {
  const botao = document.getElementById('notificacoes-optin');
  const teste = document.getElementById('notificacoes-teste');
  const estado = document.getElementById('alertas-estado');
  if (!botao || !estado) return;
  const { vapidKey, workerUrl, scope, apiUrl, testeUrl, csrf } = botao.dataset;
  const compativel = ('serviceWorker' in navigator) && ('PushManager' in window) &&
                     ('Notification' in window) && window.isSecureContext;
  let registro = null;
  let ocupado = false;

  function mensagem(texto) { estado.textContent = texto; }
  function chaveBase64(chave) {
    const base64 = (chave + '='.repeat((4 - chave.length % 4) % 4))
      .replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), c => c.charCodeAt(0));
  }
  async function post(acao, subscription, url = apiUrl) {
    const form = new URLSearchParams({ csrf_token: csrf });
    if (acao) form.set('acao', acao);
    if (subscription) form.set('subscription', JSON.stringify(subscription.toJSON()));
    const response = await fetch(url, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: form.toString()
    });
    let json = {};
    try { json = await response.json(); } catch (_) {}
    if (!response.ok || !json.ok) throw new Error(json.erro || 'falha_no_servidor');
  }
  async function preparar() {
    if (!compativel || !vapidKey) return null;
    if (!registro) registro = await navigator.serviceWorker.register(workerUrl, { scope });
    return registro;
  }
  async function atualizar() {
    if (!compativel) {
      botao.disabled = true;
      teste.hidden = true;
      mensagem('Web Push indisponível: use HTTPS ou localhost e um navegador compatível.');
      return;
    }
    if (!vapidKey) {
      botao.disabled = true;
      teste.hidden = true;
      mensagem('O servidor ainda precisa configurar as chaves VAPID.');
      return;
    }
    if (Notification.permission === 'denied') {
      botao.disabled = true;
      teste.hidden = true;
      mensagem('Notificações bloqueadas. Altere a permissão nas configurações do navegador.');
      return;
    }
    try {
      const sw = await preparar();
      const inscricao = await sw.pushManager.getSubscription();
      const ativado = !!inscricao && Notification.permission === 'granted';
      botao.textContent = ativado ? 'Desativar' : 'Ativar';
      teste.hidden = !ativado;
      mensagem(ativado ? 'Notificações push ativas neste dispositivo.' : 'Notificações desativadas neste dispositivo.');
      // Recupera uma inscrição existente ao entrar em outra sessão no mesmo navegador.
      if (ativado) await post('ativar', inscricao);
    } catch (_) {
      mensagem('Falha ao consultar a inscrição. Confira o Service Worker e a conexão.');
    }
  }
  botao.addEventListener('click', async () => {
    if (ocupado) return;
    ocupado = true; botao.disabled = true;
    try {
      if (!compativel || !vapidKey) throw new Error('web_push_indisponivel');
      const sw = await preparar();
      const anterior = await sw.pushManager.getSubscription();
      if (anterior) {
        await post('desativar', anterior);
        const removida = await anterior.unsubscribe();
        if (!removida) throw new Error('nao_foi_possivel_cancelar');
        mensagem('Notificações desativadas.');
      } else {
        // Sempre durante a interação iniciada pelo usuário.
        const permissao = await Notification.requestPermission();
        if (permissao !== 'granted') throw new Error('permissao_recusada');
        const inscricao = await sw.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: chaveBase64(vapidKey)
        });
        try { await post('ativar', inscricao); }
        catch (e) { await inscricao.unsubscribe(); throw e; }
        mensagem('Notificações ativadas neste dispositivo!');
      }
    } catch (error) {
      const iphone = /iPad|iPhone|iPod/.test(navigator.userAgent) &&
        !window.matchMedia('(display-mode: standalone)').matches;
      mensagem(iphone ? 'No iPhone, adicione o Zubbo à Tela de Início para ativar avisos.'
        : 'Não foi possível ativar: ' + (error.message || 'erro desconhecido') + '.');
    } finally {
      ocupado = false; botao.disabled = false;
      await atualizar();
    }
  });
  teste?.addEventListener('click', async () => {
    teste.disabled = true;
    try { await post(null, null, testeUrl); mensagem('Notificação de teste solicitada.'); }
    catch (_) { mensagem('Não foi possível enviar o teste agora.'); }
    finally { teste.disabled = false; }
  });
  atualizar();
})();
