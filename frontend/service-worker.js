/**
 * Service worker minimal — cache l'app shell (pages + assets statiques)
 * pour un fonctionnement hors-ligne partiel, comme prévu au cahier des
 * charges (PWA, section 5). Les appels vers /backend-php/api/... ne sont
 * JAMAIS mis en cache : les données doivent toujours être fraîches.
 */

const CACHE_NAME = 'benin-vivant-v1';
const APP_SHELL = [
  'index.html',
  'assets/js/bv-api.js',
  'manifest.json',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((noms) =>
      Promise.all(noms.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Jamais de cache pour les appels API : toujours du réseau, données fraîches
  if (url.pathname.includes('/api/')) {
    return;
  }

  // Stratégie "cache d'abord, réseau en secours" pour les pages/assets statiques
  event.respondWith(
    caches.match(event.request).then((reponseCache) => {
      return reponseCache || fetch(event.request).then((reponseReseau) => {
        return caches.open(CACHE_NAME).then((cache) => {
          if (event.request.method === 'GET' && reponseReseau.status === 200) {
            cache.put(event.request, reponseReseau.clone());
          }
          return reponseReseau;
        });
      }).catch(() => reponseCache);
    })
  );
});
