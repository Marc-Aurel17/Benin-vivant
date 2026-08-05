<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/fedapay.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('initier_don', 10, 60);

$body = getJsonBody();
$projetId = isset($body['projet_id']) ? (int) $body['projet_id'] : 0;
$montant = isset($body['montant']) ? (int) $body['montant'] : 0;
$donateurNom = cleanString($body['donateur_nom'] ?? 'Donateur anonyme', 150);
$donateurEmail = filter_var(trim($body['donateur_email'] ?? ''), FILTER_VALIDATE_EMAIL);
$methode = $body['methode_paiement'] ?? 'fedapay'; // 'mtn_momo' | 'moov_money' | 'fedapay' (carte)

if ($projetId <= 0 || $montant < 100) {
    jsonError('Projet et montant (minimum 100 FCFA) requis.', 422);
}
if (!$donateurEmail) {
    jsonError('Email valide requis (reçu de paiement + suivi).', 422);
}

$pdo = getPDO();

// Vérifie que le projet existe réellement avant de créer une transaction
$stmt = $pdo->prepare('SELECT id, titre FROM projets_patrimoine WHERE id = ?');
$stmt->execute([$projetId]);
$projet = $stmt->fetch();
if (!$projet) {
    jsonError('Projet introuvable.', 404);
}

// Référence unique interne, utilisée pour retrouver la transaction au retour/webhook
$referenceInterne = 'BV-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));

try {
    // 1. Création de la transaction côté FedaPay
    $creation = fedapayRequest('POST', '/transactions', [
        'description'   => 'Don — ' . $projet['titre'],
        'amount'        => $montant,
        'currency'      => ['iso' => 'XOF'],
        'callback_url'  => FRONTEND_URL . '/merci.html?ref=' . $referenceInterne,
        'merchant_reference' => $referenceInterne,
        'customer' => [
            'firstname' => $donateurNom,
            'email'     => $donateurEmail,
        ],
    ]);

    if ($creation['status'] >= 400) {
        throw new RuntimeException($creation['body']['message'] ?? 'FedaPay a refusé la création de la transaction.');
    }

    $transactionId = $creation['body']['v1/transaction']['id'] ?? $creation['body']['id'] ?? null;
    if (!$transactionId) {
        throw new RuntimeException('Réponse FedaPay inattendue (pas d\'ID de transaction).');
    }

    // 2. Génération du lien de paiement (token) pour cette transaction
    $token = fedapayRequest('POST', "/transactions/{$transactionId}/token");
    $urlPaiement = $token['body']['url'] ?? null;

    if (!$urlPaiement) {
        throw new RuntimeException('Impossible de générer le lien de paiement.');
    }

    // 3. Enregistrement en base, statut "en_attente" — le webhook confirmera le succès
    $insert = $pdo->prepare(
        'INSERT INTO dons (projet_id, user_id, donateur_nom, donateur_email, montant, devise, methode_paiement, reference_transaction, statut, created_at)
         VALUES (?, ?, ?, ?, ?, \'XOF\', ?, ?, \'en_attente\', NOW())'
    );
    $insert->execute([
        $projetId,
        currentUser()['id'] ?? null,
        $donateurNom,
        $donateurEmail,
        $montant,
        $methode,
        $referenceInterne,
    ]);

    logSecurityEvent('don_initie', currentUser()['id'] ?? null, [
        'reference' => $referenceInterne, 'montant' => $montant, 'projet_id' => $projetId,
    ]);

    jsonResponse([
        'message' => 'Transaction créée.',
        'reference' => $referenceInterne,
        'url_paiement' => $urlPaiement,
    ], 201);

} catch (RuntimeException $e) {
    error_log('Erreur FedaPay (initier don) : ' . $e->getMessage());
    jsonError('Impossible d\'initier le paiement pour le moment : ' . $e->getMessage(), 502);
}
