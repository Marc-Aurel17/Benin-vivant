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

  function render() {
    const host = document.getElementById('bv-admin-sidebar');
    if (!host) return;

    const current = currentFile();
    const isDashboard = current === 'admin-dashboard.html';

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
