# Guide de test complet — Bénin Vivant

Suis ces étapes dans l'ordre. Ne saute rien : chaque étape dépend de la précédente.

---

## 1. Où placer le zip

Décompresse `Benin-Vivant-Projet-Complet.zip`. Tu obtiens un dossier
`Benin-Vivant-Projet-Complet/` contenant `backend-php/`, `frontend/`, etc.

Copie **deux dossiers** (et seulement ces deux-là) dans `htdocs/` de XAMPP,
**côte à côte, pas l'un dans l'autre** :

```
C:\xampp\htdocs\backend-php\    ← contenu de Benin-Vivant-Projet-Complet/backend-php/
C:\xampp\htdocs\frontend\       ← contenu de Benin-Vivant-Projet-Complet/frontend/
```

(Sous Linux : `/opt/lampp/htdocs/backend-php/` et `/opt/lampp/htdocs/frontend/`)

⚠️ Vérifie bien qu'après copie, tu as `htdocs/backend-php/api/...` et pas
`htdocs/backend-php/backend-php/api/...` (erreur classique en copiant le
mauvais niveau de dossier).

Garde les autres dossiers (`database/`, `docs/`, `dossier-soumission/`,
`ai-service/`) où tu veux sur ton PC — pas besoin qu'ils soient dans htdocs.

---

## 2. Démarrer XAMPP

Ouvre le panneau XAMPP → démarre **Apache** et **MySQL**. Les deux doivent
être verts. Si Apache refuse de démarrer, un autre programme utilise déjà le
port 80 (Skype, IIS...) — soit tu le fermes, soit tu changes le port
d'Apache dans la config XAMPP (et adapte alors toutes les URLs ci-dessous).

---

## 3. Créer et importer la base de données

1. Va sur `http://localhost/phpmyadmin`
2. Onglet **Bases de données** → crée une base nommée exactement `benin_vivant`,
   interclassement `utf8mb4_general_ci`
3. Clique sur cette base → onglet **Importer**
4. Sélectionne `database/schema.sql` → Exécuter (en bas de page)
5. **Recommence l'import**, cette fois avec `database/contenu_reel.sql`
   (dans le même ordre : schema d'abord, contenu ensuite — sinon les tables
   n'existent pas encore et l'import échoue)

### Vérification rapide
Dans phpMyAdmin, clique sur la table `users` → tu dois voir 4 comptes :
1 super admin (`admin@benin-vivant.bj`) + 3 guides (`*.guide@example.com`).
Clique sur `groupes_ethniques` → tu dois voir 7 lignes. Si l'une des deux
tables est vide, reprends l'import de `contenu_reel.sql`.

---

## 4. Configurer les fichiers PHP

Ouvre ces fichiers dans `backend-php/config/` et vérifie/adapte :

| Fichier | Ce qu'il faut vérifier |
|---|---|
| `database.php` | `DB_USER` = `root`, `DB_PASS` = vide (défaut XAMPP) |
| `config.php` | `APP_URL` = `http://localhost/backend-php`, `FRONTEND_URL` = `http://localhost/frontend` |
| `fedapay.php` | Remplace les clés `sandbox_XXXX...` par tes vraies clés sandbox (dashboard FedaPay) — pas obligatoire pour tester le reste du site, seulement pour le module Dons |
| `mail.php` | Remplace `SMTP_USER`/`SMTP_PASS` par un vrai compte Gmail + mot de passe d'application — pas obligatoire sauf pour tester le reçu email |

Si tu ne configures pas FedaPay/mail tout de suite, ce n'est pas grave : le
reste du site fonctionne sans, seul le paiement échouera proprement (message
d'erreur clair, pas de crash).

---

## 5. Test niveau 1 — L'API répond-elle ?

Ouvre ces URLs une par une dans ton navigateur. Tu dois voir du texte JSON
(pas une page blanche, pas une erreur PHP) :

```
http://localhost/backend-php/api/ethnies/list.php
http://localhost/backend-php/api/sites/list.php
http://localhost/backend-php/api/evenements/list.php
http://localhost/backend-php/api/projets/list.php
http://localhost/backend-php/api/guides/list.php
```

✅ Attendu : `{"data":[...]}` avec du contenu dedans (pas un tableau vide).
❌ Si tu vois une erreur PHP (texte blanc/rouge, "Fatal error") → prends une
capture d'écran, c'est un bug de code, pas de config.
❌ Si tu vois une page blanche totalement vide → active `display_errors` en
regardant `backend-php/config/config.php` (`APP_ENV` doit être `'local'`).

---

## 6. Test niveau 2 — Le frontend public (sans compte)

Ouvre `http://localhost/frontend/index.html`. Navigue et vérifie :

| Page | Ce que tu dois voir |
|---|---|
| `index.html` | La page d'accueil s'affiche (maquette statique, normal) |
| `encyclopedie.html` | Les **7 vraies fiches ethnies** se chargent (Fon, Yoruba, Bariba...) — pas les données de démo génériques |
| `site-detail.html` | Ouvre-la depuis un lien de la carte ou de l'encyclopédie ; les infos GPS réelles s'affichent |
| `carte.html` | Une vraie carte Leaflet avec les marqueurs des sites réels ; teste le bouton de géolocalisation (le navigateur doit demander l'autorisation) |
| `evenements.html` | Les 5 vrais événements (Vodun Days, Gaani, Egungun, Fête des ignames, Dantokpa) |
| `evenement-detail.html` | Clique sur un événement → sa fiche complète avec carte |
| `guides.html` | Les 3 guides de démo (Rachidatou, Ismaël, Fabrice) |
| `guide-detail.html` | Clique sur un guide → sa fiche + le formulaire de contact (envoie un message test, vérifie que ça répond "envoyé") |
| `projets.html` | Les 3 projets réels avec leur jauge de financement |
| `projet-detail.html` | Clique sur un projet → sa fiche complète |
| `signalement.html` | Remplis le formulaire, clique sur la carte pour placer un point, envoie → message de confirmation |
| `contact.html` | Envoie un message de contact ET teste l'inscription newsletter séparément |
| `assistant.html` | Le chatbot IA — ne fonctionnera que si le microservice Python tourne (voir étape 9) |

---

## 7. Test niveau 3 — Comptes utilisateurs

### Connexion guide
1. Va sur `mon-espace.html`
2. Connecte-toi avec `rachidatou.guide@example.com` / `DemoGuide2026!`
3. Vérifie que tu restes connecté si tu navigues vers une autre page puis reviens
4. Teste la déconnexion

### Inscription d'un nouveau compte
1. Sur `mon-espace.html`, crée un nouveau compte (contributeur) avec un email que tu contrôles
2. Reconnecte-toi avec ce nouveau compte

### Connexion admin
1. Connecte-toi avec `admin@benin-vivant.bj` / `BeninVivant2026!`
2. ⚠️ **Change immédiatement ce mot de passe** une fois connecté (il est écrit en clair dans le fichier SQL)

---

## 8. Test niveau 4 — Le parcours de don complet

Seulement si tu as configuré `fedapay.php` (étape 4) :

1. Lance un tunnel ngrok (`ngrok http 80`) — **obligatoire pour le webhook**,
   voir `docs/TEST-LOCAL-FEDAPAY.md` si tu ne l'as pas encore fait
2. Configure l'URL de webhook dans le dashboard FedaPay sandbox avec l'URL
   ngrok + `/backend-php/api/dons/webhook.php`
3. Va sur `http://localhost/frontend/dons.html`
4. Fais un don, utilise le numéro test `64000001` sur la page FedaPay
5. Tu dois être redirigé vers `merci.html` avec un statut "en attente" puis
   "réussi" (rafraîchissement automatique après quelques secondes)
6. Si `mail.php` est configuré : vérifie ta boîte mail (et les spams) pour
   le reçu automatique

**Ne t'inquiète pas si tu veux juste tester sans FedaPay configuré** — passe
directement à l'étape 9, ce n'est qu'un module parmi d'autres.

---

## 9. Test niveau 5 — Microservice IA (optionnel)

```bash
cd ai-service
python -m venv venv
venv\Scripts\activate        # Windows — ou `source venv/bin/activate` sous Linux/Mac
pip install -r requirements.txt
set AI_SERVICE_API_KEY=une-cle-de-test-quelconque   # Windows — `export` sous Linux/Mac
uvicorn main:app --reload --port 8001
```
Puis retourne sur `assistant.html` et pose une question.

---

## 10. Pages qui restent des maquettes (normal, pas un bug)

Ces pages affichent du contenu **statique** — c'est attendu, elles n'ont pas
encore d'endpoint API dédié : `a-propos.html`, `actualites.html`,
`article-detail.html`, `contribuer.html`, `devenir-admin.html`, `faq.html`,
`langues.html`, `mediatheque.html`, `partenaires.html`, `quiz.html`, et
toutes les pages `admin-*.html` / `super-admin-theme.html`.

Si tu cliques dedans et que rien ne se passe (formulaires qui ne soumettent
pas, boutons inactifs), **ce n'est pas cassé** — c'est simplement pas encore
branché. Dis-moi lesquelles tu veux qu'on connecte en priorité.

---

## 11. Où regarder si quelque chose ne marche pas

| Symptôme | Où regarder |
|---|---|
| Page blanche sur un endpoint API | `backend-php/config/config.php` → `APP_ENV = 'local'` doit être actif pour voir l'erreur PHP réelle |
| "Erreur serveur" générique côté frontend | Ouvre la Console (F12) du navigateur → onglet Network → clique sur la requête en échec → regarde la réponse |
| Rien ne se charge du tout, erreur CORS dans la console | Vérifie que tu accèdes bien via `http://localhost/frontend/...` et pas en ouvrant le fichier HTML directement (double-clic) — il faut qu'Apache serve les deux dossiers |
| Connexion refusée même avec le bon mot de passe | Vérifie dans phpMyAdmin que le compte existe bien dans `users` et que `is_active = 1` |
| Logs serveur PHP | `C:\xampp\apache\logs\error.log` (Windows) |
| Logs de sécurité applicatifs | Table `audit_logs` dans phpMyAdmin (connexions, actions admin, etc.) |

---

## Récapitulatif express (si tu veux juste la checklist)

- [ ] `backend-php/` et `frontend/` copiés dans `htdocs/`, côte à côte
- [ ] Apache + MySQL démarrés
- [ ] Base `benin_vivant` créée, `schema.sql` puis `contenu_reel.sql` importés
- [ ] `config/database.php` vérifié
- [ ] 5 endpoints API testés dans le navigateur → JSON avec du contenu
- [ ] Navigation dans les 15 pages connectées, contenu réel visible
- [ ] Connexion guide + admin testées
- [ ] (Optionnel) Don test + webhook + reçu email
- [ ] (Optionnel) Chatbot IA
