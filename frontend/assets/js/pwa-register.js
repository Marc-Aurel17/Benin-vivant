/**
 * Enregistre le service worker (app shell + cache API) sur toutes les pages.
 * Silencieux si le navigateur ne supporte pas les service workers, ou si le
 * site est ouvert en file:// (le SW exige http/https).
 */
if ('serviceWorker' in navigator && (location.protocol === 'http:' || location.protocol === 'https:')) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('service-worker.js').catch((err) => {
      console.warn('Service worker non enregistré :', err.message);
    });
  });
}
