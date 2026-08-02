# Passer de FedaPay sandbox à live

Le code est déjà prêt à basculer (une seule ligne à changer dans
`config/fedapay.php`), mais **la partie administrative ne peut être faite que
par toi** — c'est ton compte, ta société/association, tes documents.
Voici exactement ce qu'il faut faire, dans l'ordre.

## Ce que je ne peux pas faire à ta place

FedaPay exige une vérification d'identité/entreprise avant d'autoriser le
mode live (c'est une obligation réglementaire liée aux paiements, pas une
option). Concrètement, sur https://dashboard.fedapay.com (pas le sous-domaine
sandbox cette fois), section **Vérification du compte** :

1. Informations sur l'organisation (nom, statut juridique — ONG, entreprise,
   association, ou personnel selon ton cas)
2. Pièce d'identité du représentant (CNI ou passeport)
3. Justificatif de l'organisation si applicable (RCCM, statuts d'association,
   IFU béninois...)
4. Coordonnées bancaires ou Mobile Money où seront reversés les fonds collectés
5. Validation par l'équipe FedaPay — compte quelques jours ouvrés

Une fois validé, le dashboard te donne accès à un nouveau jeu de clés qui
commencent par `pk_live_...` et `sk_live_...` (au lieu de `_sandbox_`).

## Ce que tu dois changer une fois validé

### 1. Les clés API
Dans `backend-php/config/fedapay.php` :
```php
define('FEDAPAY_ENVIRONMENT', 'live');   // était 'sandbox'
define('FEDAPAY_PUBLIC_KEY', 'pk_live_...');
define('FEDAPAY_SECRET_KEY', 'sk_live_...');
```
⚠️ Une garde de sécurité a été ajoutée dans ce fichier : si tu passes en
`'live'` sans remplacer les clés, le site refusera de démarrer avec un
message d'erreur explicite plutôt que d'échouer silencieusement.

### 2. Le webhook — nouvelle URL, nouveau secret
En sandbox, tu utilisais ngrok pour exposer `localhost`. En production, ton
site a une vraie adresse publique — plus besoin de tunnel.

1. Dashboard FedaPay (mode live) → **Développement → Webhooks**
2. Ajoute l'URL définitive : `https://ton-vrai-domaine.bj/backend-php/api/dons/webhook.php`
3. Coche les mêmes événements qu'en sandbox (`transaction.approved`,
   `transaction.declined`, `transaction.canceled`)
4. Récupère le **nouveau** secret webhook (différent de celui du sandbox) et
   mets-le dans `FEDAPAY_WEBHOOK_SECRET`

### 3. Le reste de la config à vérifier en même temps
Puisque tu es en train de basculer en production, vérifie aussi :

| Fichier | À faire |
|---|---|
| `config/config.php` | `APP_ENV` → `'production'` (désactive l'affichage détaillé des erreurs PHP au public) ; `APP_URL`/`FRONTEND_URL` → ton vrai domaine |
| `config/database.php` | Identifiants de la vraie base de production (pas root sans mot de passe) |
| `config/mail.php` | Un compte SMTP dédié à la production, pas ton Gmail personnel de test |
| `uploads/webhook-debug.log` | Vide ce fichier (ou supprime-le) — il ne doit pas traîner en production |
| Certificat HTTPS | Obligatoire — FedaPay live n'accepte pas les webhooks en HTTP simple |

## Tester avant l'ouverture réelle au public

Fais un **vrai** micro-don de test (le plus petit montant possible, ex: 100
FCFA) avec ton propre argent une fois tout basculé, pour confirmer que le
circuit complet fonctionne aussi en live (transaction → webhook → reçu email
→ mise à jour du montant collecté). C'est la seule façon de vraiment savoir
que ça marche : le mode sandbox ne garantit pas à 100% que le live sera
identique (délais réseau différents, comportement du vrai opérateur Mobile
Money, etc.).

## Ordre recommandé

1. Termine d'abord tous tes tests en sandbox (voir `TEST-LOCAL-FEDAPAY.md`)
2. Demande la vérification de compte FedaPay — ça prend du temps, lance-la tôt
3. Pendant l'attente, prépare l'hébergement définitif du site (domaine, HTTPS)
4. Une fois validé : bascule les clés, configure le nouveau webhook
5. Fais le micro-don de test réel
6. Ouvre au public
