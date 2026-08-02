<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/fedapay.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

requireRole('admin', 'super_admin');

$resultats = [
    'environnement' => FEDAPAY_ENVIRONMENT,
    'cle_secrete_configuree' => !str_contains(FEDAPAY_SECRET_KEY, 'XXXX'),
    'cle_webhook_configuree' => !str_contains(FEDAPAY_WEBHOOK_SECRET, 'XXXX'),
];

try {
    // Appel léger qui ne crée rien : liste les devises supportées.
    $test = fedapayRequest('GET', '/currencies');
    $resultats['connexion_api'] = $test['status'] === 200 ? 'ok' : 'echec';
    $resultats['code_http'] = $test['status'];
    $resultats['details'] = $test['status'] === 200
        ? 'Connexion FedaPay opérationnelle.'
        : ($test['body']['message'] ?? 'Réponse inattendue de FedaPay.');
} catch (RuntimeException $e) {
    $resultats['connexion_api'] = 'echec';
    $resultats['details'] = $e->getMessage();
}

jsonResponse(['data' => $resultats]);
