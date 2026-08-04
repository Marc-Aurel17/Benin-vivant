<?php
/**
 * Config générale + démarrage sécurisé de session.
 * À inclure en tout premier sur chaque page/endpoint qui a besoin de session.
 */

// Adapte selon ton hôte local XAMPP. En production (Render), ces valeurs
// viennent des variables d'environnement définies dans le dashboard Render.
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/backend-php');
define('FRONTEND_URL', getenv('FRONTEND_URL') ?: 'http://localhost/frontend'); // adapte si ton dossier frontend a un autre nom/emplacement
define('APP_ENV', getenv('APP_ENV') ?: 'local'); // 'local' | 'production'

// --- Microservice IA (Module 7 — Guide culturel intelligent) ------------
// Clé partagée avec ai-service/.env (AI_SERVICE_API_KEY) : ne JAMAIS exposer
// cette clé au navigateur, seul ce backend PHP appelle le microservice.
define('AI_SERVICE_URL', getenv('AI_SERVICE_URL') ?: 'http://localhost:8000');
define('AI_SERVICE_API_KEY', getenv('AI_SERVICE_API_KEY') ?: 'change-moi-avec-une-cle-aleatoire-longue');

// --- Session sécurisée -------------------------------------------------
ini_set('session.cookie_httponly', 1);   // JS ne peut pas lire le cookie de session (anti-XSS)
ini_set('session.use_strict_mode', 1);   // refuse les IDs de session non générés par le serveur

if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);      // cookie envoyé uniquement en HTTPS
    // IMPORTANT : sur Render, le frontend (ex: benin-vivant-frontend-xxxx.onrender.com)
    // et le backend (ex: benin-vivant-backend-xxxx.onrender.com) sont deux sous-domaines
    // *.onrender.com — un domaine que les navigateurs traitent comme une "public suffix"
    // (comme *.github.io), donc chaque sous-domaine compte comme un site DIFFÉRENT pour
    // les cookies. Avec SameSite=Strict (ou même Lax), le navigateur n'envoie JAMAIS le
    // cookie de session sur les appels fetch() du frontend vers le backend : la connexion
    // "réussit" (le cookie est bien posé par le serveur) mais aucune requête suivante ne le
    // renvoie, donc la session semble "ne pas persister". SameSite=None (+ Secure, déjà activé
    // ci-dessus) est nécessaire ici.
    ini_set('session.cookie_samesite', 'None');
} else {
    ini_set('session.cookie_samesite', 'Strict'); // anti CSRF cross-site (OK en local, même origine)
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Affichage des erreurs : jamais en production -----------------------
if (APP_ENV === 'local') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
