/**
 * Bénin Vivant — client API JavaScript partagé (vanilla, aucune dépendance).
 *
 * Toutes les requêtes passent par credentials:'include' pour transmettre le
 * cookie de session PHP. Le jeton CSRF (reçu à la connexion) est conservé
 * uniquement en mémoire JS — jamais en localStorage — et joint automatiquement
 * sur toute requête qui modifie des données (POST/PATCH/DELETE).
 *
 * Adapte API_BASE selon l'endroit où tourne ton backend PHP (XAMPP).
 */
const API_BASE = window.BENIN_VIVANT_API_BASE || 'http://localhost/backend-php/api';

const BeninVivantAPI = (() => {
  let csrfToken = sessionStorage.getItem('bv_csrf') || null; // survit à un refresh, pas au navigateur fermé

  async function request(path, options = {}) {
    const isFormData = options.body instanceof FormData;
    const headers = isFormData
      ? { ...(options.headers || {}) } // laisser le navigateur fixer Content-Type + boundary
      : { 'Content-Type': 'application/json', ...(options.headers || {}) };
    const method = (options.method || 'GET').toUpperCase();

    if (csrfToken && ['POST', 'PATCH', 'DELETE'].includes(method)) {
      headers['X-CSRF-Token'] = csrfToken;
    }

    // Timeout strict : sans ça, une requête qui ne répond jamais (backend en
    // cold start, connexion réseau qui traîne) laisse la Promise en attente
    // indéfiniment. gardeAdmin() (appelé sur CHAQUE page admin) attend cette
    // Promise avant d'afficher quoi que ce soit : la page semble figée, puis
    // le navigateur finit par la considérer comme plantée et propose de la
    // fermer. 15s laisse largement le temps à un cold start Render normal.
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000);

    let response;
    try {
      response = await fetch(`${API_BASE}${path}`, { credentials: 'include', headers, signal: controller.signal, ...options });
    } catch (networkError) {
      if (networkError.name === 'AbortError') {
        throw new Error('Le serveur met trop de temps à répondre. Réessayez dans quelques instants.');
      }
      throw new Error('Impossible de joindre le serveur. Vérifiez que le backend PHP est démarré.');
    } finally {
      clearTimeout(timeoutId);
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.error || `Erreur ${response.status}`);
    }
    return data;
  }

  function setCsrfToken(token) {
    csrfToken = token;
    sessionStorage.setItem('bv_csrf', token);
  }

  function clearSession() {
    csrfToken = null;
    sessionStorage.removeItem('bv_csrf');
  }

  return {
    // --- Auth ---
    async whoami() {
      const data = await request('/auth/me.php');
      if (data.csrf_token) setCsrfToken(data.csrf_token);
      return data.user;
    },
    async login(email, password) {
      const data = await request('/auth/login.php', { method: 'POST', body: JSON.stringify({ email, password }) });
      setCsrfToken(data.csrf_token);
      return data.user;
    },
    async register(payload) {
      return request('/auth/register.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async logout() {
      await request('/auth/logout.php', { method: 'POST' });
      clearSession();
    },

    // --- Réglages du site (thème dynamique, Module super admin) ---
    async getSettings() {
      const data = await request('/settings/get.php');
      return data.data;
    },

    // --- Encyclopédie (Module 1) ---
    async listEthnies() {
      const data = await request('/ethnies/list.php');
      return data.data;
    },
    async getEthnie(slug) {
      const data = await request(`/ethnies/detail.php?slug=${encodeURIComponent(slug)}`);
      return data.data;
    },

    // --- Sites historiques (Module 3) ---
    async listSites(lat, lng) {
      const params = new URLSearchParams();
      if (lat !== undefined && lng !== undefined) {
        params.set('lat', lat);
        params.set('lng', lng);
      }
      const data = await request(`/sites/list.php?${params.toString()}`);
      return data.data;
    },
    async getSite(slug) {
      const data = await request(`/sites/detail.php?slug=${encodeURIComponent(slug)}`);
      return data.data;
    },

    // --- Événements (Module 13) ---
    async listEvenements(filtres = {}) {
      const params = new URLSearchParams(filtres);
      const data = await request(`/evenements/list.php?${params.toString()}`);
      return data.data;
    },
    async getEvenement(slug) {
      const data = await request(`/evenements/detail.php?slug=${encodeURIComponent(slug)}`);
      return data.data;
    },
    async marquerInteret(evenementId, email) {
      return request('/evenements/interet.php', {
        method: 'POST',
        body: JSON.stringify({ evenement_id: evenementId, email }),
      });
    },
    icsUrl(slug) {
      return `${API_BASE}/evenements/export-ics.php?slug=${encodeURIComponent(slug)}`;
    },

    // --- Signalements (Module 9) ---
    async envoyerSignalement(formData) {
      return request('/signalements/create.php', { method: 'POST', body: formData });
    },

    // --- Contact & Newsletter ---
    async envoyerContact(payload) {
      return request('/contact/send.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async abonnerNewsletter(email) {
      return request('/newsletter/subscribe.php', { method: 'POST', body: JSON.stringify({ email }) });
    },

    // --- Dons / Paiement (Module 11) ---
    async initierDon(payload) {
      // payload: { projet_id, montant, donateur_nom, donateur_email, methode_paiement }
      return request('/dons/initier.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async statutDon(reference) {
      return request(`/dons/retour.php?ref=${encodeURIComponent(reference)}`);
    },

    // --- Guide culturel IA (Module 7) ---
    async guideIA(question, langue = 'fr') {
      return request('/ia/chat.php', { method: 'POST', body: JSON.stringify({ question, langue }) });
    },

    // --- Projets de sauvegarde (Module 10) ---
    async listProjets() {
      const data = await request('/projets/list.php');
      return data.data;
    },
    async getProjet(slug) {
      const data = await request(`/projets/detail.php?slug=${encodeURIComponent(slug)}`);
      return data.data;
    },

    // --- Guides touristiques (Module 4) ---
    async listGuides() {
      const data = await request('/guides/list.php');
      return data.data;
    },
    async getGuide(id) {
      const data = await request(`/guides/detail.php?id=${encodeURIComponent(id)}`);
      return data.data;
    },
    async contacterGuide(payload) {
      return request('/guides/contact.php', { method: 'POST', body: JSON.stringify(payload) });
    },

    // --- Quiz & badges (Module 6) ---
    async listQuizQuestions(theme, nombre = 10) {
      const data = await request(`/quiz/questions.php?theme=${encodeURIComponent(theme)}&nombre=${nombre}`);
      return data.data;
    },
    async validerQuiz(questionId, reponse) {
      return request('/quiz/valider.php', { method: 'POST', body: JSON.stringify({ question_id: questionId, reponse }) });
    },
    async terminerQuiz(theme, bonnesReponses, totalQuestions) {
      return request('/quiz/terminer.php', {
        method: 'POST',
        body: JSON.stringify({ theme, bonnes_reponses: bonnesReponses, total_questions: totalQuestions }),
      });
    },

    // --- Carte des langues (Module 5) ---
    async listLangues() {
      const data = await request('/langues/list.php');
      return data.data;
    },

    // --- Actualités (Module 12) ---
    async listActualites() {
      const data = await request('/actualites/list.php');
      return data.data;
    },
    async getActualite(slug) {
      const data = await request(`/actualites/detail.php?slug=${encodeURIComponent(slug)}`);
      return data.data;
    },

    // --- Contribution communautaire (Module 8) ---
    async creerContribution(payload) {
      return request('/contributions/create.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async mesContributions() {
      const data = await request('/contributions/mes-contributions.php');
      return data.data;
    },
    async mesBadges() {
      const data = await request('/quiz/mes-badges.php');
      return data.data;
    },
    async mesSignalements() {
      const data = await request('/signalements/mes-signalements.php');
      return data.data;
    },
    async guideMesDemandes() {
      const data = await request('/guides/mes-demandes.php');
      return data.data;
    },
    async guideMarquerDemande(id, statut) {
      return request('/guides/mes-demandes.php', { method: 'PATCH', body: JSON.stringify({ id, statut }) });
    },
    async adminDemandesContactGuides() {
      const data = await request('/admin/demandes-contact-guides.php');
      return data.data;
    },
    async listHistoire() {
      const data = await request('/histoire/list.php');
      return data.data;
    },
    async adminNewsletterList() {
      const data = await request('/admin/newsletter-manage.php');
      return data.data;
    },
    async adminNewsletterDelete(id) {
      return request('/admin/newsletter-manage.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },
    async adminContactsList() {
      const data = await request('/admin/contacts-manage.php');
      return data.data;
    },
    async adminContactsMarkRead(id) {
      return request('/admin/contacts-manage.php', { method: 'PATCH', body: JSON.stringify({ id }) });
    },
    async adminContactsDelete(id) {
      return request('/admin/contacts-manage.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },

    // --- Guides (admin) ---
    async adminGuidesList() {
      const data = await request('/admin/guides-manage.php');
      return data.data;
    },
    async adminGuidesProcess(id, statut) {
      return request('/admin/guides-manage.php', { method: 'PATCH', body: JSON.stringify({ id, statut }) });
    },

    // --- Contributeurs (admin) ---
    async adminContributeursList() {
      const data = await request('/admin/contributeurs-manage.php');
      return data.data;
    },
    async adminContributeursProcess(id, action) {
      return request('/admin/contributeurs-manage.php', { method: 'PATCH', body: JSON.stringify({ id, action }) });
    },
    async adminContributeursDelete(id) {
      return request('/admin/contributeurs-manage.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },

    // --- Dons (admin) ---
    async adminDonsList() {
      const data = await request('/admin/dons-list.php');
      return data.data;
    },

    // --- Propositions de projets ---
    async proposerProjet(payload) {
      return request('/propositions/create.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async adminPropositionsList() {
      const data = await request('/admin/propositions-manage.php');
      return data.data;
    },
    async adminPropositionsProcess(id, statut) {
      return request('/admin/propositions-manage.php', { method: 'PATCH', body: JSON.stringify({ id, statut }) });
    },

    // --- Actualités (admin CRUD) ---
    async adminActualitesList() {
      const data = await request('/admin/actualites-manage.php');
      return data.data;
    },
    async adminActualitesCreer(payload) {
      return request('/admin/actualites-manage.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async adminActualitesTogglePublication(id) {
      return request('/admin/actualites-manage.php', { method: 'PATCH', body: JSON.stringify({ id, action: 'toggle_publication' }) });
    },
    async adminActualitesSupprimer(id) {
      return request('/admin/actualites-manage.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },
    async adminActualitesModifier(payload) {
      return request('/admin/actualites-manage.php', { method: 'PATCH', body: JSON.stringify({ action: 'modifier', ...payload }) });
    },

    // --- Partenaires (admin CRUD) ---
    async adminPartenairesList() {
      const data = await request('/admin/partenaires-manage.php');
      return data.data;
    },
    async adminPartenairesCreer(payload) {
      return request('/admin/partenaires-manage.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async adminPartenairesSupprimer(id) {
      return request('/admin/partenaires-manage.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },
    async adminPartenairesModifier(payload) {
      return request('/admin/partenaires-manage.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },

    // --- Témoignages ---
    async listTemoignages() {
      const data = await request('/temoignages/list.php');
      return data.data;
    },
    async soumettreTemoignage(payload) {
      return request('/temoignages/list.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async adminTemoignagesList() {
      const data = await request('/admin/temoignages-manage.php');
      return data.data;
    },
    async adminTemoignagesToggle(id) {
      return request('/admin/temoignages-manage.php', { method: 'PATCH', body: JSON.stringify({ id }) });
    },
    async adminTemoignagesSupprimer(id) {
      return request('/admin/temoignages-manage.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },

    // --- Langues (admin CRUD) ---
    async adminLanguesList() {
      const data = await request('/admin/langues-manage.php');
      return data.data;
    },
    async adminLanguesCreer(payload) {
      return request('/admin/langues-manage.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async adminLanguesAjouterMot(langueId, motExpression, traductionFr) {
      return request('/admin/langues-manage.php', { method: 'POST', body: JSON.stringify({ action: 'ajouter_mot', langue_id: langueId, mot_expression: motExpression, traduction_fr: traductionFr }) });
    },
    async adminLanguesSupprimer(id, type = 'langue') {
      return request('/admin/langues-manage.php', { method: 'DELETE', body: JSON.stringify({ id, type }) });
    },
    async adminLanguesModifier(payload) {
      return request('/admin/langues-manage.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },

    // --- Quiz (admin CRUD) ---
    async adminQuizList() {
      const data = await request('/admin/quiz-manage.php');
      return data.data;
    },
    async adminQuizCreer(payload) {
      return request('/admin/quiz-manage.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async adminQuizSupprimer(id) {
      return request('/admin/quiz-manage.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },
    async adminQuizModifier(payload) {
      return request('/admin/quiz-manage.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },

    // --- Histoire / frise (admin CRUD) ---
    async adminHistoireList() {
      const data = await request('/admin/histoire-manage.php');
      return data.data;
    },
    async adminHistoireCreer(payload) {
      return request('/admin/histoire-manage.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async adminHistoireSupprimer(id, type) {
      return request('/admin/histoire-manage.php', { method: 'DELETE', body: JSON.stringify({ id, type }) });
    },
    async adminHistoireModifier(payload) {
      return request('/admin/histoire-manage.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },

    // --- Projets (admin CRUD) ---
    async adminProjetsList() {
      const data = await request('/admin/projets-manage.php');
      return data.data;
    },
    async adminProjetsCreer(payload) {
      return request('/admin/projets-manage.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async adminProjetsProcess(id, statut) {
      return request('/admin/projets-manage.php', { method: 'PATCH', body: JSON.stringify({ id, statut }) });
    },

    // --- Recherche globale (admin) ---
    async rechercheGlobale(terme) {
      const data = await request(`/admin/recherche-globale.php?q=${encodeURIComponent(terme)}`);
      return data.data;
    },

    // --- Médiathèque, partenaires, FAQ ---
    async listMediatheque(categorie = null) {
      const q = categorie ? `?categorie=${encodeURIComponent(categorie)}` : '';
      const data = await request(`/mediatheque/list.php${q}`);
      return data.data;
    },
    async uploaderMedia(titre, categorie, fichier) {
      const formData = new FormData();
      formData.append('titre', titre);
      formData.append('categorie', categorie);
      formData.append('fichier', fichier);
      const response = await fetch(`${API_BASE}/admin/mediatheque-upload.php`, {
        method: 'POST', credentials: 'include', body: formData,
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.error || `Erreur ${response.status}`);
      return data;
    },
    async supprimerMedia(id) {
      return request('/admin/mediatheque-delete.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },
    async listPartenaires() {
      const data = await request('/partenaires/list.php');
      return data.data;
    },
    async listFaq() {
      const data = await request('/faq/list.php');
      return data.data;
    },

    // --- Inscription admin en 3 étapes ---
    async demandeAdminDemarrer(payload) {
      return request('/admin-requests/start.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async demandeAdminVerifierEmail(demandeId, code) {
      return request('/admin-requests/verify-email.php', { method: 'POST', body: JSON.stringify({ demande_id: demandeId, code }) });
    },
    async demandeAdminUploadIdentite(demandeId, fichier) {
      const formData = new FormData();
      formData.append('demande_id', demandeId);
      formData.append('piece_identite', fichier);
      const response = await fetch(`${API_BASE}/admin-requests/upload-identity.php`, {
        method: 'POST', credentials: 'include', body: formData,
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.error || `Erreur ${response.status}`);
      return data;
    },

    // --- Utilitaire géolocalisation navigateur ---
    localiser() {
      return new Promise((resolve) => {
        if (!navigator.geolocation) return resolve(null);
        navigator.geolocation.getCurrentPosition(
          (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
          () => resolve(null),
          { enableHighAccuracy: true, timeout: 8000 }
        );
      });
    },

    // --- Panneau admin ---
    async dashboardStats() {
      const data = await request('/admin/dashboard-stats.php');
      return data.data;
    },
    async adminContenuList() {
      const data = await request('/admin/contenu-list.php');
      return data.data;
    },
    async publierSite(id) {
      return request('/sites/publish.php', { method: 'PATCH', body: JSON.stringify({ id }) });
    },
    async publierEthnie(id) {
      return request('/ethnies/publish.php', { method: 'PATCH', body: JSON.stringify({ id }) });
    },
    async creerSite(payload) {
      return request('/sites/create.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async modifierSite(payload) {
      return request('/sites/update.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },
    async supprimerSite(id) {
      return request('/sites/delete.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },
    async creerEthnie(payload) {
      return request('/ethnies/create.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async modifierEthnie(payload) {
      return request('/ethnies/update.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },
    async supprimerEthnie(id) {
      return request('/ethnies/delete.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },
    async creerEvenementPublic(payload) {
      return request('/evenements/create.php', { method: 'POST', body: JSON.stringify(payload) });
    },
    async modifierEvenement(payload) {
      return request('/evenements/update.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },
    async supprimerEvenement(id) {
      return request('/evenements/delete.php', { method: 'DELETE', body: JSON.stringify({ id }) });
    },
    async adminContributionsList(statut = 'en_attente') {
      const data = await request(`/admin/contributions-list.php?statut=${encodeURIComponent(statut)}`);
      return data.data;
    },
    async adminContributionsProcess(id, action, commentaire = '') {
      return request('/admin/contributions-process.php', { method: 'PATCH', body: JSON.stringify({ id, action, commentaire }) });
    },
    async adminSignalementsList() {
      const data = await request('/admin/signalements-list.php');
      return data.data;
    },
    async adminSignalementsProcess(id, statut) {
      return request('/admin/signalements-process.php', { method: 'PATCH', body: JSON.stringify({ id, statut }) });
    },
    async adminEvenementsProcess(id, action, commentaire = '') {
      return request('/admin/evenements-process.php', { method: 'PATCH', body: JSON.stringify({ id, action, commentaire }) });
    },
    async adminEvenementsList() {
      const data = await request('/admin/evenements-list.php');
      return data.data;
    },
    async adminDemandesList() {
      const data = await request('/admin/demandes-list.php');
      return data.data;
    },
    async adminDemandesProcess(id, action, commentaire = '') {
      return request('/admin/demandes-process.php', { method: 'PATCH', body: JSON.stringify({ id, action, commentaire }) });
    },
    async adminComptesList() {
      const data = await request('/admin/comptes-list.php');
      return data.data;
    },
    async adminComptesProcess(id, action) {
      const method = action === 'supprimer' ? 'DELETE' : 'PATCH';
      return request('/admin/comptes-process.php', { method, body: JSON.stringify({ id, action }) });
    },
    async diagnosticFedapay() {
      const data = await request('/admin/diagnostic-fedapay.php');
      return data.data;
    },
    async diagnosticEmail(email) {
      return request('/admin/diagnostic-email.php', { method: 'POST', body: JSON.stringify({ email }) });
    },
    async updateSettings(payload) {
      return request('/settings/update.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },
    async updateProfile(payload) {
      return request('/auth/update-profile.php', { method: 'PATCH', body: JSON.stringify(payload) });
    },
    async adminLogsList(limite = 20) {
      const data = await request(`/admin/logs-list.php?limite=${limite}`);
      return data.data;
    },
  };
})();
