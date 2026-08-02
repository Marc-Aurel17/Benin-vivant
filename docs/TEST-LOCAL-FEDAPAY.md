# Tester FedaPay en local (XAMPP) — pourquoi et comment

## Le problème, précisément

Quand un visiteur paie, il y a **deux communications distinctes** :

1. **Le navigateur du visiteur** → redirigé vers la page de paiement FedaPay,
   puis FedaPay le redirige vers ton `callback_url` (ex: `retour.php`).
   Ça, ça marche très bien avec `http://localhost`, car c'est **le navigateur
   du visiteur** qui fait la redirection — il tourne sur ta machine.

2. **Le serveur de FedaPay** → doit appeler ton `webhook.php` directement,
   serveur à serveur, pour te confirmer que le paiement a réellement abouti
   (le navigateur seul n'est pas fiable : l'utilisateur peut fermer l'onglet,
   couper sa connexion, etc. juste après le paiement).

Le point 2 est celui qui casse : **les serveurs de FedaPay, sur Internet, ne
savent pas ce qu'est `localhost`** — pour eux, `localhost` désigne leurs
propres serveurs, pas ta machine. Il n'existe aucun moyen de configurer
FedaPay pour qu'il "sache" atteindre ton PC directement : ton PC n'a pas
d'adresse publique fixe et est probablement derrière une box/routeur (NAT).

**La solution : un tunnel.** Un petit programme qui ouvre une connexion
sortante depuis ta machine vers un serveur public, et relaie tout ce qui
arrive sur une URL publique (`https://xxxx.ngrok-free.app`) vers ton
`localhost`. FedaPay appelle l'URL publique, le tunnel relaie vers XAMPP.

```
FedaPay (Internet) → https://xxxx.ngrok-free.app → [tunnel] → localhost/backend-php/api/dons/webhook.php
```

## Étape 1 — Installer ngrok (le plus simple pour débuter)

1. Va sur https://ngrok.com/download, crée un compte gratuit
2. Télécharge ngrok pour ton OS, décompresse
3. Récupère ton "authtoken" personnel sur le dashboard ngrok, puis :
   ```bash
   ngrok config add-authtoken TON_TOKEN_ICI
   ```

## Étape 2 — Démarrer XAMPP puis le tunnel

1. Lance Apache + MySQL depuis le panneau XAMPP (comme d'habitude)
2. Vérifie que ton site répond bien en local :
   `http://localhost/backend-php/api/evenements/list.php`
3. Ouvre un terminal et lance :
   ```bash
   ngrok http 80
   ```
   (80 = le port par défaut d'Apache sous XAMPP ; si tu as changé le port,
   adapte la commande, ex: `ngrok http 8080`)

4. ngrok affiche quelque chose comme :
   ```
   Forwarding   https://a1b2-XX-XX-XX-XX.ngrok-free.app -> http://localhost:80
   ```
   Cette URL `https://a1b2-....ngrok-free.app` est **temporaire** (elle
   change à chaque redémarrage de ngrok en version gratuite) et **publique** :
   n'importe qui sur Internet peut l'atteindre pendant que ngrok tourne.

5. Teste-la tout de suite dans ton navigateur :
   ```
   https://a1b2-XX-XX-XX-XX.ngrok-free.app/backend-php/api/evenements/list.php
   ```
   Si tu vois le même JSON qu'en local → le tunnel fonctionne, XAMPP est bien
   exposé publiquement.

## Étape 3 — Configurer le webhook dans le dashboard FedaPay

1. Connecte-toi sur https://sandbox-dashboard.fedapay.com (bien le mode
   **sandbox/test**, pas live, pour tes tests)
2. Va dans **Développement → Webhooks**
3. Ajoute une nouvelle URL de webhook :
   ```
   https://a1b2-XX-XX-XX-XX.ngrok-free.app/backend-php/api/dons/webhook.php
   ```
4. Sélectionne au minimum les événements : `transaction.approved`,
   `transaction.declined`, `transaction.canceled`
5. FedaPay te donne un **secret de webhook** (`wh_sandbox_...`) — copie-le
   dans `backend-php/config/fedapay.php`, constante `FEDAPAY_WEBHOOK_SECRET`

⚠️ **Chaque fois que tu relances ngrok (version gratuite), l'URL change** —
il faudra remettre à jour l'URL du webhook dans le dashboard FedaPay à chaque
session de test. C'est la contrepartie de la gratuité ; en payant ngrok (ou
avec Cloudflare Tunnel configuré en mode nommé), l'URL peut rester fixe.

## Étape 4 — Récupère tes clés API sandbox

Dans le même dashboard, **Développement → Clés API** :
- Clé publique (`pk_sandbox_...`)
- Clé secrète (`sk_sandbox_...`)

Colle-les dans `backend-php/config/fedapay.php` (`FEDAPAY_PUBLIC_KEY`,
`FEDAPAY_SECRET_KEY`).

## Étape 5 — Tester un paiement de bout en bout

1. Va sur `http://localhost/frontend/dons.html` (en local, pas besoin du
   tunnel pour cette partie — seul le webhook a besoin d'être public)
2. Fais un don test.

### Numéros de test (important : ça a changé récemment chez FedaPay)

FedaPay a simplifié son bac à sable : il n'y a plus de numéros spécifiques
par opérateur (MTN/Moov séparés). **Un seul mode de test unifié** s'applique
en sandbox, quel que soit l'opérateur choisi à l'écran :

| Scénario | Numéro à utiliser |
|---|---|
| ✅ Paiement réussi | `64000001` ou `66000001` |
| ❌ Paiement échoué (simulation) | N'importe quel autre numéro |

Aucun vrai compte Mobile Money n'est débité en sandbox, évidemment — ces
numéros ne servent qu'à FedaPay pour simuler la réponse de l'opérateur.

3. Observe en direct :
   - Le terminal ngrok affiche la requête entrante (`POST /backend-php/api/dons/webhook.php 200 OK`)
   - Le fichier `backend-php/uploads/webhook-debug.log` contient le payload brut reçu
   - La table `dons` dans phpMyAdmin passe de `en_attente` à `reussi`
   - **Un email de reçu est envoyé automatiquement** à l'adresse renseignée
     dans le formulaire de don (voir section suivante — nécessite d'avoir
     configuré `config/mail.php` au préalable)

## Étape 5bis — Le reçu par email

Dès que le webhook confirme un paiement réussi (`transaction.approved`),
`api/dons/webhook.php` génère un reçu HTML (`includes/recu_don.php`) et
l'envoie automatiquement au donateur via `config/mail.php` (SMTP + PHPMailer,
déjà inclus dans `backend-php/lib/PHPMailer/` — pas besoin de Composer).

**Avant de tester**, configure `backend-php/config/mail.php` :
```php
define('SMTP_USER', 'ton-adresse@gmail.com');
define('SMTP_PASS', 'xxxx xxxx xxxx xxxx'); // mot de passe d'application, PAS ton mot de passe Gmail
```
Pour Gmail : active la validation en 2 étapes sur le compte, puis génère un
mot de passe d'application sur https://myaccount.google.com/apppasswords —
copie ces 16 caractères tels quels (avec ou sans espaces, peu importe).

Tu peux tester l'envoi SMTP **sans faire de vrai don**, via l'endpoint de
diagnostic (connecté en admin) :
```
POST http://localhost/backend-php/api/admin/diagnostic-email.php
Body: {"email": "ton-adresse-de-test@exemple.com"}
```
Si l'email arrive (vérifie aussi les spams), la config SMTP est bonne — le
webhook enverra les reçus de la même façon automatiquement.

## Étape 6 — Vérifier rapidement sans faire de vrai paiement

Utilise l'endpoint de diagnostic (connecté en admin) :
```
http://localhost/backend-php/api/admin/diagnostic-fedapay.php
```
Il confirme que tes clés API sont valides et que la connexion sortante vers
FedaPay fonctionne — sans créer de transaction ni passer par le webhook.

## Débogage courant

| Symptôme | Cause probable |
|---|---|
| ngrok affiche `ERR_NGROK_...` au démarrage | Authtoken pas configuré, relance `ngrok config add-authtoken` |
| L'URL ngrok renvoie une page HTML d'avertissement avant l'API | Normal sur le plan gratuit lors du premier accès navigateur ; les appels API (comme ceux de FedaPay) passent directement, ignore ce message pour les tests navigateur ou ajoute le header `ngrok-skip-browser-warning: true` |
| Webhook jamais reçu, `webhook-debug.log` reste vide | L'URL webhook dans le dashboard FedaPay ne correspond plus à l'URL ngrok actuelle (elle a changé) |
| `Erreur de connexion à FedaPay` dans les logs PHP | Pare-feu Windows ou antivirus qui bloque les connexions sortantes cURL — autorise `php.exe` / Apache dans le pare-feu |
| Signature invalide en permanence | `FEDAPAY_WEBHOOK_SECRET` ne correspond pas à celui affiché dans le dashboard pour CETTE URL de webhook précise (chaque URL de webhook a son propre secret) |
| Le paiement réussit mais aucun email n'arrive | Vérifie `config/mail.php` (SMTP_USER/SMTP_PASS), regarde les logs PHP pour le message d'erreur exact de PHPMailer, teste d'abord avec `api/admin/diagnostic-email.php` |
| Erreur "SMTP connect() failed" | Le port 587 est bloqué par un pare-feu/antivirus, ou le mot de passe d'application Gmail est incorrect/expiré |

## Alternative à ngrok : Cloudflare Tunnel

Si tu préfères éviter la limite de session gratuite de ngrok :
```bash
# Installation (Windows via winget, ou télécharge cloudflared.exe directement)
winget install --id Cloudflare.cloudflared

# Lancement (pas besoin de compte pour un tunnel "quick")
cloudflared tunnel --url http://localhost:80
```
Il affiche une URL `https://xxxx.trycloudflare.com` à utiliser exactement de
la même façon que l'URL ngrok ci-dessus.

## ⚠️ Avant de mettre en ligne pour de vrai

- Remplace toutes les clés `sandbox_` par les clés `live_` une fois le site
  hébergé sur un vrai serveur avec un vrai nom de domaine
- Supprime ou protège `uploads/webhook-debug.log` (déjà bloqué en accès
  direct via `.htaccess`, mais autant vider le fichier avant la mise en prod)
- Remplace l'URL webhook ngrok/cloudflared par l'URL définitive de ton
  domaine dans le dashboard FedaPay **mode live**
