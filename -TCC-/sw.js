/* Zubbo Web Push: escopo limitado à instalação atual (Railway ou XAMPP). */
self.addEventListener('push', (event) => {
  let dados = {};
  try { if (event.data) dados = event.data.json(); } catch (_) { /* payload opcional */ }
  const scope = new URL(self.registration.scope);
  let destino = new URL('./app/views/notificacoes/notificacoes.php', scope);
  if (typeof dados.url === 'string') {
    try {
      const proposto = new URL('.' + dados.url, scope);
      if (proposto.origin === scope.origin && proposto.pathname.startsWith(scope.pathname + 'app/views/')) {
        destino = proposto;
      }
    } catch (_) { /* segue destino seguro */ }
  }
  const titulo = typeof dados.title === 'string' ? dados.title.slice(0, 70) : 'Zubbo';
  const corpo = typeof dados.body === 'string' ? dados.body.slice(0, 150) : 'Você tem uma nova notificação.';
  event.waitUntil(self.registration.showNotification(titulo, {
    body: corpo,
    icon: new URL('public/imagem/ZubboSemFundo.png', scope).href,
    badge: new URL('public/imagem/ZubboSemFundo.png', scope).href,
    tag: typeof dados.tag === 'string' ? dados.tag : 'zubbo',
    data: { url: destino.href }
  }));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil((async () => {
    const scope = new URL(self.registration.scope);
    let destino = new URL('./app/views/notificacoes/notificacoes.php', scope);
    try {
      const salvo = new URL(event.notification.data?.url || '', scope);
      if (salvo.origin === scope.origin && salvo.pathname.startsWith(scope.pathname + 'app/views/')) destino = salvo;
    } catch (_) { /* destino padrão */ }
    const abas = await clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const aba of abas) {
      if (aba.url.startsWith(scope.href) && 'focus' in aba) {
        await aba.navigate(destino.href);
        return aba.focus();
      }
    }
    return clients.openWindow(destino.href);
  })());
});
