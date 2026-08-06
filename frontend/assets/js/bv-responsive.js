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

    /* Le contenu admin/public est souvent rendu après un fetch : on ré-applique. */
    let pending = null;
    new MutationObserver(() => {
      clearTimeout(pending);
      pending = setTimeout(() => { enhanceTables(); tagInlineGrids(); }, 120);
    }).observe(document.body, { childList: true, subtree: true });
  });
})();
