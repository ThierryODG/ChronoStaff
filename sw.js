// ChronoStaff Mobile PWA Service Worker v18.0 (Network First)
const CACHE_NAME = 'chronostaff-pwa-v18';
const ASSETS_TO_CACHE = [
  './',
  './index.html',
  './manifest.json',
  './css/app.css?v=18.0',
  './js/api-config.js?v=1',
  './js/app-core.js?v=18.0',
  './js/app-lifecycle.js?v=18.0',
  './js/app-events.js?v=18.0',
  './js/app-navigation.js?v=18.0',
  './js/app-auth.js?v=18.0',
  './js/app-punch.js?v=18.0',
  './js/app-history.js?v=18.0',
  './js/app-account.js?v=18.0',
  './js/app-manager.js?v=18.0',
  './js/app.js?v=18.0',
  './logo.png',
  './icons/icon.png',
  './icons/icon-192.png',
  './icons/icon-512.png',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
  'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'
];

// ==========================================
// INSTALL & ACTIVATE (Purge tous les anciens caches)
// ==========================================
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.allSettled(ASSETS_TO_CACHE.map(url => cache.add(url)));
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

// ==========================================
// FETCH : Network First avec fallback Cache
// Permet d'avoir toujours la toute dernière version de l'application
// ==========================================
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Pour les appels API : Réseau direct, avec message JSON si hors ligne
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        return new Response(JSON.stringify({
          success: false,
          offline: true,
          message: 'Vous êtes hors-ligne. Le pointage sera sauvegardé localement.'
        }), {
          headers: { 'Content-Type': 'application/json' },
          status: 503
        });
      })
    );
    return;
  }

  // Pour les pages et assets de l'application : Network First
  event.respondWith(
    fetch(event.request).then((networkResponse) => {
      if (event.request.method === 'GET' && networkResponse.status === 200) {
        const clone = networkResponse.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
      }
      return networkResponse;
    }).catch(() => caches.match(event.request))
  );
});

// ==========================================
// NOTIFICATIONS PUSH — rappels envoyés par le backend
// ==========================================
self.addEventListener('push', (event) => {
  let payload = {};
  if (event.data) {
    try {
      payload = event.data.json();
    } catch {
      payload = { body: event.data.text() };
    }
  }

  event.waitUntil(self.registration.showNotification(
    payload.title || 'ChronoStaff — Rappel de pointage',
    {
      body: payload.body || 'N’oubliez pas de badger.',
      icon: './icons/icon.png',
      badge: './icons/icon.png',
      tag: payload.tag || 'chronostaff-reminder',
      data: { url: payload.url || './' },
      requireInteraction: false,
    }
  ));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = new URL(event.notification.data?.url || './', self.location.href).href;
  event.waitUntil(
    self.clients.matchAll({ type: 'window' }).then((clientList) => {
      const client = clientList.find((windowClient) => windowClient.url === targetUrl);
      if (client) {
        return client.focus();
      }
      return self.clients.openWindow(targetUrl);
    })
  );
});
