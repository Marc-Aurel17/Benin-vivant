/**
 * Bénin Vivant — améliorations responsive transverses.
 * Ne modifie aucun contenu : ajoute seulement les comportements nécessaires
 * aux petits écrans (tableaux, tiroir admin, grilles inline, menus).
 */
(function () {
  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  /* 1. Tableaux : conteneur défilant + libellés pour le mode carte mobile. */
  function enhanceTables(root) {
    (root || document).querySelectorAll('table').forEach((table) => {
      if (!table.parentElement || !table.parentElement.classList.contains('bv-table-scroll')) {
        const box = document.createElement('div');
        box.className = 'bv-table-scroll';
        table.parentNode.insertBefore(box, table);
        box.appendChild(table);
      }
      const heads = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
      if (!heads.length) return;
      table.querySelectorAll('tbody tr').forEach((tr) => {
        Array.from(tr.children).forEach((td, i) => {
          if (heads[i] && !td.hasAttribute('data-label')) td.setAttribute('data-label', heads[i]);
        });
      });
    });
  }

  /* 2. Grilles définies en style inline (contenu injecté par JS). */
  function tagInlineGrids(root) {
    (root || document).querySelectorAll('[style*="grid-template-columns"]').forEach((el) => {
      const v = el.style.gridTemplateColumns || '';
      if (v && v.trim() !== '1fr' && !el.hasAttribute('data-bv-grid')) el.setAttribute('data-bv-grid', '');
    });
  }

  /* 3. Tiroir admin : fond assombri, fermeture au clic extérieur / Échap. */
  function enhanceAdminDrawer() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (!sidebar) return;
    let backdrop = null;

    function close() {
      sidebar.classList.remove('bv-open');
      document.body.classList.remove('bv-no-scroll');
      if (backdrop) { backdrop.remove(); backdrop = null; }
    }
    function open() {
      document.body.classList.add('bv-no-scroll');
      if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'bv-backdrop';
        backdrop.addEventListener('click', close);
        document.body.appendChild(backdrop);
      }
    }

    new MutationObserver(() => {
      if (sidebar.classList.contains('bv-open')) open(); else close();
    }).observe(sidebar, { attributes: true, attributeFilter: ['class'] });

    sidebar.addEventListener('click', (e) => {
      if (e.target.closest('a')) close();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 1000) close();
    });
  }

  /* 4. Menu public : fermeture au changement de largeur + verrou de scroll. */
  function enhancePublicNav() {
    const links = document.getElementById('bv-nav-links');
    if (!links) return;
    new MutationObserver(() => {
      document.body.classList.toggle('bv-no-scroll', links.classList.contains('open') && window.innerWidth <= 640);
    }).observe(links, { attributes: true, attributeFilter: ['class'] });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 900) {
        links.classList.remove('open');
        document.body.classList.remove('bv-no-scroll');
      }
    });
  }

  ready(() => {
    enhanceTables();
    tagInlineGrids();
    enhanceAdminDrawer();
    enhancePublicNav();

<<<<<<< HEAD
    /* Le contenu admin/public est souvent rendu après un fetch (ex: un
       tableau rempli via innerHTML après un appel API) : on doit ré-appliquer
       enhanceTables()/tagInlineGrids() à ce moment-là.
       Un MutationObserver sur tout document.body (subtree:true) est fragile :
       il se redéclenche pour CHAQUE changement du DOM de toute la page, y
       compris ceux produits par d'autres scripts (sidebar admin réinjectée
       à chaque navigation, thème, etc.), ce qui peut créer une cascade de
       ré-observations qui gèle l'onglet. On observe seulement les
       conteneurs qui reçoivent effectivement du contenu dynamique (tbody
       des tableaux, grilles), pas tout le document. */
    let pending = null;
    function runEnhancements() {
      clearTimeout(pending);
      pending = setTimeout(() => {
        enhanceTables();
        tagInlineGrids();
      }, 150);
    }

    // Observe chaque <tbody> présent au chargement (toutes les pages admin
    // remplissent leur tableau via innerHTML après un appel API, mais l'id
    // du tbody diffère d'une page à l'autre — voir admin-*.html). On observe
    // aussi #kpiGrid quand il existe (dashboard). Jamais tout <body> : c'était
    // la cause de la cascade de mutations qui gelait l'onglet.
    const ciblesSurveillees = [
      ...document.querySelectorAll('table tbody'),
      document.getElementById('kpiGrid'),
    ].filter(Boolean);

    ciblesSurveillees.forEach((cible) => {
      // childList seul (pas subtree) : on réagit uniquement quand des <tr>
      // sont ajoutées/retirées (rechargement de la liste), jamais quand on
      // pose nous-mêmes l'attribut data-label sur les <td> à l'intérieur —
      // ça évite tout risque de re-déclenchement en cascade sur nos propres
      // modifications.
      new MutationObserver(runEnhancements).observe(cible, { childList: true });
    });
=======
    /* Le contenu admin/public est souvent rendu après un fetch : on ré-applique. */
    let pending = null;
    new MutationObserver(() => {
      clearTimeout(pending);
      pending = setTimeout(() => { enhanceTables(); tagInlineGrids(); }, 120);
    }).observe(document.body, { childList: true, subtree: true });
>>>>>>> 07901c2 (Petit REtour 2)
  });
})();
