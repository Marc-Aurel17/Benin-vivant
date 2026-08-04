/**
 * Bénin Vivant — sidebar admin unifiée.
 *
 * Avant : seule admin-dashboard.html avait la sidebar complète (20 liens) ;
 * toutes les autres pages admin n'affichaient que 3-4 liens "apparentés",
 * obligeant à repasser par le dashboard pour changer de section.
 * Après : la même sidebar complète est injectée partout, avec la page
 * courante marquée "active".
 *
 * Usage : remplacer le contenu de <aside class="admin-sidebar"> par
 * <aside class="admin-sidebar" id="bv-admin-sidebar"></aside> et inclure ce
 * script (après admin-guard.js).
 */
(function () {
  const GROUPS = [
    {
      label: "Vue d'ensemble",
      items: [
        ['admin-dashboard.html', 'Tableau de bord', 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z'],
      ],
    },
    {
      label: 'Contenu (cahier des charges)',
      items: [
        ['admin-contenu.html', 'Ethnies (Module 1)'],
        ['admin-contenu.html', 'Sites historiques (Module 3)'],
        ['admin-histoire.html', 'Frise historique (Module 2)'],
        ['admin-langues.html', 'Langues (Module 5)'],
        ['admin-quiz.html', 'Quiz & badges (Module 6)'],
        ['admin-guides.html', 'Guides touristiques (Module 4)'],
        ['admin-projets.html', 'Projets de sauvegarde (Module 10)'],
        ['admin-actualites.html', 'Actualités (Module 12)'],
        ['admin-mediatheque.html', 'Médiathèque'],
        ['admin-partenaires.html', 'Partenaires'],
        ['admin-temoignages.html', 'Témoignages'],
      ],
    },
    {
      label: 'Interactions publiques',
      items: [
        ['admin-signalements.html', 'Signalements (Module 9)'],
        ['admin-contributions.html', 'Contributions (Module 8)'],
        ['admin-propositions.html', 'Propositions de projets'],
        ['admin-dons.html', 'Dons (Module 11)'],
        ['admin-messages.html', 'Contacts & newsletter'],
      ],
    },
    {
      label: 'Comptes',
      items: [
        ['admin-contributeurs.html', 'Contributeurs'],
        ['admin-comptes.html', 'Comptes admin'],
        ['admin-demandes.html', "Demandes d'inscription"],
      ],
    },
    {
      label: 'Super admin',
      items: [
        ['super-admin-theme.html', 'Personnalisation & thème'],
        ['admin-diagnostic.html', "Logs d'activité & diagnostic FedaPay"],
      ],
    },
  ];

  function currentFile() {
    const path = window.location.pathname;
    return path.substring(path.lastIndexOf('/') + 1) || 'admin-dashboard.html';
  }

  function injectMobileToggle() {
    if (document.getElementById('bv-admin-mobile-styles')) return;
    const style = document.createElement('style');
    style.id = 'bv-admin-mobile-styles';
    style.textContent = `
      .bv-admin-burger{
        display:none; position:fixed; top:14px; left:14px; z-index:200;
        background:var(--panneau); border:1px solid var(--bordure); color:var(--parchemin);
        width:42px; height:42px; border-radius:6px; font-size:1.2rem; cursor:pointer;
      }
      @media (max-width:1000px){
        .bv-admin-burger{display:block;}
        .admin-sidebar.bv-open{
          display:block !important; position:fixed; top:0; left:0; bottom:0; width:82%;
          max-width:320px; overflow-y:auto; z-index:190; box-shadow:8px 0 30px rgba(0,0,0,.5);
        }
        .admin-main{padding-top:4.5rem;}
      }
    `;
    document.head.appendChild(style);

    const burger = document.createElement('button');
    burger.className = 'bv-admin-burger';
    burger.type = 'button';
    burger.setAttribute('aria-label', 'Ouvrir le menu admin');
    burger.textContent = '☰';
    document.body.appendChild(burger);

    burger.addEventListener('click', () => {
      const sidebar = document.getElementById('bv-admin-sidebar');
      sidebar.classList.toggle('bv-open');
    });
  }

  function render() {
    const host = document.getElementById('bv-admin-sidebar');
    if (!host) return;

    const current = currentFile();
    const isDashboard = current === 'admin-dashboard.html';
    injectMobileToggle();

    // Le premier groupe ("Vue d'ensemble") est géré à part plus bas car son
    // lien "Recherche globale" a un comportement spécial selon la page.
    const groupsHtml = GROUPS.slice(1).map((group) => {
      const linksHtml = group.items
        .map(([href, label]) => {
          const active = href === current ? ' class="active"' : '';
          return `<a href="${href}"${active}>${label}</a>`;
        })
        .join('\n');
      return `
        <div class="admin-nav-group">
          <span class="admin-nav-label">${group.label}</span>
          <nav class="admin-nav">${linksHtml}</nav>
        </div>
      `;
    }).join('\n');

    // Le lien "Recherche globale" ne fonctionne que sur le dashboard (c'est
    // là que vit le champ #rechercheInput) ; ailleurs, il y renvoie.
    const rechercheLink = isDashboard
      ? `<a href="#" onclick="event.preventDefault(); document.getElementById('rechercheInput')?.focus();">Recherche globale</a>`
      : `<a href="admin-dashboard.html#recherche">Recherche globale</a>`;

    host.innerHTML = `
      <a href="admin-dashboard.html" class="logo" style="text-decoration:none; margin-bottom:2.2rem; display:block; padding:0 .6rem;">Bénin <span>Vivant</span></a>
      <div class="admin-nav-group">
        <span class="admin-nav-label">Vue d'ensemble</span>
        <nav class="admin-nav">
          <a href="admin-dashboard.html"${isDashboard ? ' class="active"' : ''}>Tableau de bord</a>
          ${rechercheLink}
        </nav>
      </div>
      ${groupsHtml}
    `;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', render);
  } else {
    render();
  }
})();
