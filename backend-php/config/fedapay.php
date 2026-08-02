<?php
/**
 * Configuration FedaPay.
 *
 * IMPORTANT : ne jamais committer de vraies clés dans Git. En local, tu peux
 * les laisser ici pour tester (le dossier n'est pas censé être public), mais
 * en production utilise des variables d'environnement (.env + getenv()).
 *
 * Où trouver ces clés : Dashboard FedaPay → Développement → Clés API et webhooks
 * https://sandbox-dashboard.fedapay.com (mode TEST/sandbox — c'est celui qu'on veut ici)
 */

define('FEDAPAY_ENVIRONMENT', getenv('FEDAPAY_ENVIRONMENT') ?: 'sandbox'); // 'sandbox' pour tester, 'live' en production

define('FEDAPAY_PUBLIC_KEY', getenv('FEDAPAY_PUBLIC_KEY') ?: 'pk_sandbox_XXXXXXXXXXXXXXXXXXXXXXXX');   // clé publique (front-end)
define('FEDAPAY_SECRET_KEY', getenv('FEDAPAY_SECRET_KEY') ?: 'sk_sandbox_XXXXXXXXXXXXXXXXXXXXXXXX');   // clé secrète (back-end uniquement)
define('FEDAPAY_WEBHOOK_SECRET', getenv('FEDAPAY_WEBHOOK_SECRET') ?: 'wh_sandbox_XXXXXXXXXXXXXXXXXXXXXXXX'); // secret du endpoint webhook

define('FEDAPAY_API_BASE', FEDAPAY_ENVIRONMENT === 'live'
    ? 'https://api.fedapay.com/v1'
    : 'https://sandbox-api.fedapay.com/v1');

// --- Garde de sécurité anti-erreur de configuration -------------------------
// Empêche deux erreurs fréquentes et coûteuses lors du passage en production :
// 1. Passer FEDAPAY_ENVIRONMENT à 'live' en oubliant de remplacer les clés
//    sandbox_/XXXX (le site croirait encaisser de l'argent réel avec des clés
//    de test, qui échoueraient silencieusement ou pire).
// 2. L'inverse : clés live_ utilisées alors qu'on est encore en environnement
//    de test (risque de créer de vraies transactions par erreur en local).
if (FEDAPAY_ENVIRONMENT === 'live') {
    if (str_contains(FEDAPAY_SECRET_KEY, 'sandbox') || str_contains(FEDAPAY_SECRET_KEY, 'XXXX')) {
        throw new RuntimeException(
            'Configuration FedaPay incohérente : FEDAPAY_ENVIRONMENT est réglé sur "live" ' .
            'mais FEDAPAY_SECRET_KEY contient encore une clé sandbox ou un placeholder. ' .
            'Remplace les clés par tes vraies clés live avant de continuer (voir docs/PASSAGE-EN-LIVE.md).'
        );
    }
} elseif (str_contains(FEDAPAY_SECRET_KEY, 'sk_live_')) {
    throw new RuntimeException(
        'Configuration FedaPay incohérente : une clé LIVE est utilisée alors que ' .
        'FEDAPAY_ENVIRONMENT est encore réglé sur "sandbox". Change FEDAPAY_ENVIRONMENT ' .
        'à "live" ou remets une clé sandbox pour continuer les tests en toute sécurité.'
    );
}

/**
 * Appel générique à l'API FedaPay via cURL (pas besoin du SDK/Composer pour
 * les opérations de base — utile puisqu'on est en PHP natif sous XAMPP).
 */
function fedapayRequest(string $method, string $endpoint, array $body = []): array
{
    $ch = curl_init(FEDAPAY_API_BASE . $endpoint);

    $headers = [
        'Authorization: Bearer ' . FEDAPAY_SECRET_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);

    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreurCurl = curl_error($ch);
    curl_close($ch);

    if ($erreurCurl) {
        // Erreur réseau (souvent : pas d'accès Internet sortant depuis XAMPP,
        // pare-feu, ou proxy d'entreprise/université qui bloque les appels sortants)
        throw new RuntimeException('Erreur de connexion à FedaPay : ' . $erreurCurl);
    }

    $decoded = json_decode($response, true) ?? [];

    return ['status' => $httpCode, 'body' => $decoded];
}
