/**
 * Bénin Vivant — navigation publique unifiée.
 *
 * Injecte le même en-tête (logo + menu complet + bouton "Mon espace") sur
 * TOUTES les pages publiques, avec repli automatique en menu mobile.
 * Évite que chaque page ait son propre sous-ensemble de liens : depuis
 * n'importe quelle page publique, on peut atteindre n'importe quelle autre.
 *
 * Usage : dans le HTML, remplacer le contenu de <header class="site-nav">
 * par un simple <header class="site-nav" id="bv-header"></header>, puis
 * inclure ce script (après env.js/bv-api.js ou avant, l'ordre n'importe pas).
 */
(function () {
  const PRIMARY_LINKS = [
    ['index.html', 'Accueil'],
    ['encyclopedie.html', 'Encyclopédie'],
    ['carte.html', 'Carte'],
    ['guides.html', 'Guides'],
    ['evenements.html', 'Événements'],
    ['projets.html', 'Projets'],
    ['dons.html', 'Dons'],
  ];

  const MORE_LINKS = [
    ['langues.html', 'Langues'],
    ['quiz.html', 'Quiz & badges'],
    ['assistant.html', 'Assistant IA'],
    ['actualites.html', 'Actualités'],
    ['mediatheque.html', 'Médiathèque'],
    ['partenaires.html', 'Partenaires'],
    ['temoignages.html', 'Témoignages'],
    ['contribuer.html', 'Contribuer'],
    ['signalement.html', 'Signaler un problème'],
    ['faq.html', 'FAQ'],
    ['a-propos.html', 'À propos'],
    ['contact.html', 'Contact'],
  ];

  const ALL_LINKS = PRIMARY_LINKS.concat(MORE_LINKS);

  function currentFile() {
    const path = window.location.pathname;
    const file = path.substring(path.lastIndexOf('/') + 1);
    return file || 'index.html';
  }

  function linkHtml(href, label, current) {
    const active = href === current ? ' class="active"' : '';
    return `<a href="${href}"${active}>${label}</a>`;
  }

  function injectStyles() {
    if (document.getElementById('bv-nav-styles')) return;
    const style = document.createElement('style');
    style.id = 'bv-nav-styles';
    style.textContent = `
      .nav-more-wrap{position:relative;}
      .nav-more-toggle{background:none; border:none; font:inherit; color:var(--parchemin-att); cursor:pointer; padding:0; display:flex; align-items:center; gap:.3rem;}
      .nav-more-toggle:hover, .nav-more-toggle[aria-expanded="true"]{color:var(--or-clair);}
      .nav-more-panel{
        display:none; position:absolute; top:calc(100% + 14px); right:0;
        background:var(--panneau); border:1px solid var(--bordure); border-radius:6px;
        box-shadow:0 12px 30px rgba(0,0,0,.45); padding:.5rem; min-width:220px; z-index:80;
      }
      .nav-more-panel.open{display:grid; gap:.1rem;}
      .nav-more-panel a{padding:.55rem .7rem; border-radius:4px; font-size:.88rem; color:var(--parchemin-att); white-space:nowrap;}
      .nav-more-panel a:hover, .nav-more-panel a.active{background:var(--panneau-2); color:var(--or-clair);}
      .nav-burger{display:none; background:none; border:1px solid var(--bordure); border-radius:5px; color:var(--parchemin); width:38px; height:38px; cursor:pointer; font-size:1.1rem;}
      @media (max-width:900px){
        .nav-links > a, .nav-more-wrap{display:none;}
        .nav-burger{display:block;}
        .nav-links{gap:0;}
        .nav-links.open{
          display:flex; flex-direction:column; align-items:stretch; gap:0;
          position:absolute; top:100%; left:0; right:0; background:var(--panneau);
          border-top:1px solid var(--bordure); padding:.6rem; z-index:80;
        }
        .nav-links.open > a, .nav-links.open .nav-more-wrap{display:block;}
        .nav-links.open .nav-more-wrap{display:none;}
        .nav-links.open a{padding:.7rem .5rem; border-bottom:1px solid var(--bordure);}
        .nav-links.open a:last-child{border-bottom:none;}
      }
    `;
    document.head.appendChild(style);
  }

  function render() {
    const host = document.getElementById('bv-header');
    if (!host) return;
    injectStyles();

    const current = currentFile();

    const primaryHtml = PRIMARY_LINKS.map(([href, label]) => linkHtml(href, label, current)).join('\n');
    const moreHtml = MORE_LINKS.map(([href, label]) => linkHtml(href, label, current)).join('\n');
    // Menu mobile : tout à plat (primaire + secondaire), pour ne jamais bloquer un parcours.
    const mobileHtml = ALL_LINKS.map(([href, label]) => linkHtml(href, label, current)).join('\n');

    host.innerHTML = `
      <div class="wrap nav-inner">
        <a href="index.html" class="logo" style="text-decoration:none;">Bénin <span>Vivant</span></a>
        <nav class="nav-links" id="bv-nav-links">
          <div class="bv-nav-desktop-only" style="display:contents;">${primaryHtml}</div>
          <div class="nav-more-wrap">
            <button class="nav-more-toggle" id="bv-nav-more-toggle" type="button" aria-expanded="false">Plus ▾</button>
            <div class="nav-more-panel" id="bv-nav-more-panel">${moreHtml}</div>
          </div>
          <div class="bv-nav-mobile-only" style="display:none;">${mobileHtml}</div>
        </nav>
        <a href="mon-espace.html" class="nav-cta" id="bv-nav-espace">Mon espace</a>
        <span id="bv-theme-slot"></span>
        <button class="nav-burger" id="bv-nav-burger" type="button" aria-label="Ouvrir le menu" aria-expanded="false">☰</button>
      </div>
    `;

    // Sur mobile, le menu déplié affiche la liste complète à plat plutôt que
    // le sous-menu "Plus", pour rester utilisable au clic/tactile.
    const navLinks = document.getElementById('bv-nav-links');
    const mobileOnly = navLinks.querySelector('.bv-nav-mobile-only');
    const desktopOnly = navLinks.querySelector('.bv-nav-desktop-only');
    const moreWrap = navLinks.querySelector('.nav-more-wrap');

    const burger = document.getElementById('bv-nav-burger');
    burger.addEventListener('click', () => {
      const isOpen = navLinks.classList.toggle('open');
      burger.setAttribute('aria-expanded', String(isOpen));
      if (isOpen) {
        mobileOnly.style.display = 'block';
        desktopOnly.style.display = 'none';
        moreWrap.style.display = 'none';
      } else {
        mobileOnly.style.display = 'none';
        desktopOnly.style.display = 'contents';
        moreWrap.style.display = '';
      }
    });

    const moreToggle = document.getElementById('bv-nav-more-toggle');
    const morePanel = document.getElementById('bv-nav-more-panel');
    moreToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = morePanel.classList.toggle('open');
      moreToggle.setAttribute('aria-expanded', String(isOpen));
    });
    document.addEventListener('click', (e) => {
      if (!morePanel.contains(e.target) && e.target !== moreToggle) {
        morePanel.classList.remove('open');
        moreToggle.setAttribute('aria-expanded', 'false');
      }
    });

    adapterBoutonEspace();
  }

  // Un admin/super_admin n'a rien à faire sur "Mon espace" (réservé aux
  // contributeurs) : on transforme le bouton en raccourci direct vers son
  // Tableau de bord. Silencieux et sans effet pour un visiteur non connecté
  // (whoami() échoue simplement en 401, on garde le lien par défaut).
  async function adapterBoutonEspace() {
    if (typeof BeninVivantAPI === 'undefined') return;
    const bouton = document.getElementById('bv-nav-espace');
    if (!bouton) return;
    try {
      const user = await BeninVivantAPI.whoami();
      if (user && (user.role === 'admin' || user.role === 'super_admin')) {
        bouton.href = 'admin-dashboard.html';
        bouton.textContent = 'Tableau de bord';
      }
    } catch (err) {
      // Non connecté ou erreur réseau : on garde "Mon espace" par défaut.
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', render);
  } else {
    render();
  }
})();
