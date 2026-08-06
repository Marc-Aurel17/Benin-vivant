/**
 * Bénin Vivant — bascule thème clair/sombre.
 * Choix mémorisé dans localStorage ; sans choix mémorisé, suit la préférence
 * système (voir aussi le repli @media dans theme-benin-vivant.css, qui évite
 * un flash clair→sombre avant que ce script ne s'exécute).
 *
 * Injecte un bouton ☀/🌙 dans #bv-theme-slot s'il existe sur la page (le
 * header injecté par site-nav.js / admin-nav.js en fournit un).
 */
(function () {
  const CLE = 'bv_theme';

  function themeActuel() {
    const stocke = localStorage.getItem(CLE);
    if (stocke === 'clair' || stocke === 'sombre') return stocke;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'sombre' : 'clair';
  }

  function appliquer(theme) {
    document.documentElement.classList.remove('bv-theme-dark', 'bv-theme-light');
    document.documentElement.classList.add(theme === 'sombre' ? 'bv-theme-dark' : 'bv-theme-light');
    const bouton = document.getElementById('bv-theme-btn');
    if (bouton) bouton.textContent = theme === 'sombre' ? '☀' : '🌙';
  }

  // Applique tout de suite (avant même DOMContentLoaded) pour éviter le flash.
  appliquer(themeActuel());

  function initBouton() {
    const slot = document.getElementById('bv-theme-slot');
    if (!slot || document.getElementById('bv-theme-btn')) return;
    const bouton = document.createElement('button');
    bouton.id = 'bv-theme-btn';
    bouton.type = 'button';
    bouton.className = 'bv-theme-switch';
    bouton.setAttribute('aria-label', 'Changer de thème clair/sombre');
    bouton.textContent = themeActuel() === 'sombre' ? '☀' : '🌙';
    bouton.addEventListener('click', () => {
      const nouveau = themeActuel() === 'sombre' ? 'clair' : 'sombre';
      localStorage.setItem(CLE, nouveau);
      appliquer(nouveau);
    });
    slot.appendChild(bouton);
  }

  // Exposé pour que site-nav.js / admin-nav.js l'appellent explicitement APRÈS
  // avoir injecté leur HTML (le bouton vit dans #bv-theme-slot, qui n'existe
  // pas encore au moment où le DOMContentLoaded de CE script se déclenche —
  // ce script est chargé dans <head>, donc son DOMContentLoaded se déclenche
  // avant celui de site-nav.js/admin-nav.js, chargés en bas de page).
  window.bvInitThemeToggle = initBouton;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBouton);
  } else {
    initBouton();
  }
})();
