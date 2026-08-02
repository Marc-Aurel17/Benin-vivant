/**
 * Bénin Vivant — footer public unifié avec plan du site complet.
 * Remplace les footers minimalistes (copyright seul) par un vrai footer de
 * navigation, identique sur toutes les pages publiques, sans lien mort.
 *
 * Usage : remplacer <footer>...</footer> par <footer id="bv-footer"></footer>
 * et inclure ce script.
 */
(function () {
  function injectStyles() {
    if (document.getElementById('bv-footer-styles')) return;
    const style = document.createElement('style');
    style.id = 'bv-footer-styles';
    // Reprend les règles de footer-grid définies dans index.html : la plupart
    // des autres pages ne les ont pas dans leur <style> inline (chaque page a
    // son propre bloc de styles, sans feuille CSS commune), d'où l'injection
    // ici pour garantir un rendu identique partout.
    style.textContent = `
      footer{border-top:1px solid var(--bordure); padding:3.5rem 0 2rem; margin-top:2rem;}
      .footer-grid{display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr; gap:2.5rem; padding-bottom:2.5rem;}
      .footer-grid h4{font-family:var(--serif); font-size:1rem; margin-bottom:1rem; color:var(--parchemin);}
      .footer-grid p, .footer-grid a{font-size:.86rem; color:var(--parchemin-att); display:block; margin-bottom:.55rem;}
      .footer-grid a:hover{color:var(--or-clair);}
      .footer-bottom{display:flex; justify-content:space-between; align-items:center; padding-top:1.8rem; border-top:1px solid var(--bordure); font-size:.82rem; color:var(--parchemin-att);}
      @media (max-width:900px){
        .footer-grid{grid-template-columns:1fr 1fr;}
        .footer-bottom{flex-direction:column; gap:.5rem; align-items:flex-start;}
      }
    `;
    document.head.appendChild(style);
  }

  function render() {
    const host = document.getElementById('bv-footer');
    if (!host) return;
    injectStyles();

    host.innerHTML = `
      <div class="wrap footer-grid">
        <div>
          <div class="logo" style="margin-bottom:1rem;">Bénin <span>Vivant</span></div>
          <p style="max-width:32ch;">Plateforme de valorisation du patrimoine culturel, historique et touristique du Bénin — Concours Digit'Héritage by Finanex.</p>
        </div>
        <div>
          <h4>Explorer</h4>
          <a href="encyclopedie.html">Encyclopédie</a>
          <a href="carte.html">Carte des sites</a>
          <a href="langues.html">Carte des langues</a>
          <a href="evenements.html">Événements</a>
          <a href="quiz.html">Quiz & badges</a>
          <a href="assistant.html">Assistant IA</a>
        </div>
        <div>
          <h4>Communauté</h4>
          <a href="guides.html">Annuaire des guides</a>
          <a href="contribuer.html">Contribuer un contenu</a>
          <a href="signalement.html">Signaler un problème</a>
          <a href="projets.html">Projets de sauvegarde</a>
          <a href="dons.html">Faire un don</a>
          <a href="devenir-admin.html">Devenir guide / admin</a>
        </div>
        <div>
          <h4>À propos</h4>
          <a href="a-propos.html">À propos du projet</a>
          <a href="temoignages.html">Témoignages</a>
          <a href="partenaires.html">Partenaires</a>
          <a href="mediatheque.html">Médiathèque</a>
          <a href="faq.html">FAQ</a>
          <a href="contact.html">Contact</a>
        </div>
      </div>
      <div class="wrap footer-bottom">
        <span>© 2026 Bénin Vivant : Racines et Diversité</span>
        <span>Connecté à l'API PHP — v1.0</span>
      </div>
    `;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', render);
  } else {
    render();
  }
})();
