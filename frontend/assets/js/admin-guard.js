/**
 * Garde d'accès pour les pages admin/super-admin.
 * À inclure APRÈS bv-api.js sur chaque page admin-*.html.
 *
 * Usage : window.gardeAdmin(['admin', 'super_admin']).then(user => { ... });
 * Redirige vers mon-espace.html si non connecté ou rôle insuffisant.
 */
window.gardeAdmin = async function (rolesAutorises = ['admin', 'super_admin']) {
  try {
    const user = await BeninVivantAPI.whoami();

    if (!user) {
      alert('Connexion requise pour accéder au panneau admin.');
      window.location.href = 'mon-espace.html';
      return null;
    }
    if (!rolesAutorises.includes(user.role)) {
      alert('Accès refusé : ce compte n\'a pas les permissions nécessaires.');
      window.location.href = 'index.html';
      return null;
    }

    // Affiche systématiquement l'utilisateur connecté dans le coin admin
    document.querySelectorAll('.admin-user').forEach(el => {
      const initiales = (user.prenom[0] || '') + (user.nom[0] || '');
      el.innerHTML = `<div class="avatar">${initiales.toUpperCase()}</div><span>${user.prenom} ${user.nom} <span style="color:var(--parchemin-att); font-family:var(--mono); font-size:.72rem;">— ${user.role}</span></span>`;
    });

    return user;
  } catch (err) {
    console.error('Erreur garde admin :', err);
    window.location.href = 'mon-espace.html';
    return null;
  }
};
