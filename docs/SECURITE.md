# Sécurité — Bénin Vivant

Résumé des protections mises en place, à citer dans le dossier de soumission (le jury Digit'Héritage valorisera un projet qui pense sécurité dès la conception).

## 1. Authentification & comptes
- Mots de passe hashés (bcrypt via `password_hash()`/`password_verify()`), jamais stockés ni loggés en clair
- Verrouillage de compte après 5 tentatives échouées (15 min) → `includes/security.php`
- 2FA disponible pour les comptes admin/modérateur
- IDs publics en UUID (pas d'ID auto-incrémenté exposé) → anti énumération/IDOR
- Vérification d'email obligatoire avant contribution

## 2. Autorisations (RBAC)
- 4 rôles stricts : visiteur, contributeur, guide, admin — fonction `requireRole()` (includes/security.php)
- Toute contribution (site, ethnie, tradition) passe par `is_published = false` par défaut → validation modérateur obligatoire avant apparition publique
- vérification d'autorisation explicite (requireAuth/requireRole) au début de chaque endpoint sensible

## 2bis. CSRF (Cross-Site Request Forgery)
- Jeton CSRF généré côté serveur à la connexion (`generateCsrfToken()`), stocké en session
- Vérifié via `hash_equals()` (comparaison en temps constant, anti timing-attack) sur toute requête POST/PATCH/DELETE authentifiée
- Cookie de session en `SameSite=Strict` en complément

## 3. Protection des données & injections
- PDO + requêtes préparées partout → aucune injection SQL possible via l’app
- Validation stricte de toutes les entrées (fonctions `cleanString()`/`validateFloat()`) : types, longueurs, bornes GPS (-90/90, -180/180)
- Sanitisation des uploads (extension, taille, re-encodage image) avant stockage
- Protection XSS : `htmlspecialchars()` systématique sur tout contenu utilisateur avant stockage et à l'affichage

## 4. Réseau & en-têtes HTTP
- HTTPS obligatoire en production (HSTS activé) → fonction `applySecurityHeaders()` (includes/functions.php) + `.htaccess`
- Content-Security-Policy stricte (scripts/styles limités aux domaines whitelistés)
- `X-Frame-Options: DENY` (anti clickjacking), `X-Content-Type-Options: nosniff`
- CORS restreint au(x) domaine(s) officiel(s), jamais de wildcard `*`

## 5. Anti-abus / rate limiting
- Login/register : 5 requêtes/min (anti brute-force et credential stuffing)
- Signalements : 10 requêtes/min (anti spam)
- Lecture publique : 60 requêtes/min
- Microservice IA : 20 requêtes/min par IP + clé API interne (jamais exposé directement au front)

## 6. Paiements (Module 11 — Dons)
- Aucune donnée bancaire stockée côté plateforme : délégué à FedaPay / MTN MoMo / Moov Money (PCI-DSS côté opérateur)
- Référence de transaction unique vérifiée côté serveur avant crédit du montant
- Toute transaction journalisée dans `audit_logs`

## 7. Chatbot IA (Module 7)
- Prompt système figé côté serveur, jamais modifiable par l'utilisateur
- Filtrage des tentatives d'injection de prompt (patterns suspects rejetés)
- Réponses limitées au contenu vérifié/publié de la base — pas d'hallucination libre
- Microservice isolé, authentifié par clé API interne, jamais appelé directement par le navigateur

## 8. Journalisation & audit
- Table `audit_logs` : connexions échouées, publications, dons, signalements
- Canal de log dédié `security.log`, conservé 90 jours
- Alertes sur tentatives d'accès non autorisé (403 loggés avec IP + route)

## 9. Bonnes pratiques de déploiement
- `APP_DEBUG=false` en production (aucune stack trace exposée)
- Sauvegardes BDD automatiques et chiffrées
- Mise à jour régulière des dépendances (vérification régulière des CVE PHP/MySQL, `npm audit` côté front)
- Séparation des environnements (.env jamais commité dans le dépôt Git)
