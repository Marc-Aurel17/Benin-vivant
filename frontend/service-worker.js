/**
 * Service worker minimal — cache l'app shell (pages + assets statiques)
 * pour un fonctionnement hors-ligne partiel, comme prévu au cahier des
 * charges (PWA, section 5). Les appels vers /backend-php/api/... ne sont
 * JAMAIS mis en cache : les données doivent toujours être fraîches.
 *
 * Stratégie : réseau d'abord, cache en secours (offline uniquement).
 * L'ancienne stratégie "cache d'abord" servait indéfiniment les JS/CSS
 * mis en cache lors de la première visite, même après un nouveau
 * déploiement — c'était la cause du "ça reste toujours aussi lent/buggé
 * après un correctif" : le navigateur ne redemandait jamais le fichier
 * au serveur tant que le nom de cache ne changeait pas.
 */

const CACHE_NAME = 'benin-vivant-v2';
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

  // Réseau d'abord (toujours la dernière version du code) ; le cache ne sert
  // que de secours si le réseau est indisponible (mode hors-ligne).
  event.respondWith(
    fetch(event.request).then((reponseReseau) => {
      if (event.request.method === 'GET' && reponseReseau.status === 200) {
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, reponseReseau.clone()));
      }
      return reponseReseau;
    }).catch(() => caches.match(event.request))
  );
});
