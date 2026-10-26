/* Zubbo Web Push: escopo limitado à instalação atual (Railway ou XAMPP). */
const offlineCache = 'zubbo-offline-v1';
const offlineUrl = new URL('public/offline.html', self.registration.scope).href;

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(offlineCache).then((cache) => cache.add(offlineUrl)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter((key) => key.startsWith('zubbo-offline-') && key !== offlineCache)
      .map((key) => caches.delete(key)));
    await self.clients.claim();
  })());
});

// Apenas a página pública de erro é armazenada; HTML autenticado e respostas da API nunca são.
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || request.mode !== 'navigate'
      || url.origin !== self.location.origin
      || !url.href.startsWith(self.registration.scope)) return;

  event.respondWith((async () => {
    try {
      const response = await fetch(request);
      if (response.status < 500) return response;
    } catch (_) { /* Sem rede: exibe a página pública armazenada. */ }
    return await caches.match(offlineUrl)
      || new Response('Zubbo indisponível. Verifique sua conexão.', {
        status: 503, headers: { 'Content-Type': 'text/plain; charset=utf-8' }
      });
  })());
});

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
