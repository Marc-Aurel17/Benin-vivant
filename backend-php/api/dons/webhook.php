<?php
/**
 * Webhook FedaPay — appelé par LEURS serveurs, jamais par ton navigateur.
 * C'est précisément pour ça que localhost ne fonctionne pas tel quel :
 * il faut un tunnel public qui redirige vers ce fichier (voir docs/TEST-LOCAL-FEDAPAY.md).
 *
 * URL à configurer dans le dashboard FedaPay (mode sandbox) :
 * https://TON-SOUS-DOMAINE.ngrok-free.app/backend-php/api/dons/webhook.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/fedapay.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/recu_don.php';

// Pas de applySecurityHeaders() ici : ce endpoint est appelé serveur-à-serveur
// par FedaPay, pas par un navigateur — le CSP/CORS n'a pas de sens ici et
// pourrait même interférer. On sécurise autrement : par la signature.

// --- 1. Lecture du corps brut (obligatoire pour vérifier la signature) -------
$payloadBrut = file_get_contents('php://input');
$signatureRecue = $_SERVER['HTTP_X_FEDAPAY_SIGNATURE'] ?? '';

file_put_contents(__DIR__ . '/../../uploads/webhook-debug.log',
    date('c') . " — Signature: {$signatureRecue}\nPayload: {$payloadBrut}\n\n",
    FILE_APPEND
); // Log de debug en local — À SUPPRIMER avant la mise en production.

if (!$signatureRecue) {
    http_response_code(400);
    exit('Signature manquante.');
}

// --- 2. Vérification de la signature (format Stripe-like : "t=...,s=...") ---
// FedaPay envoie un header du type "t=1690000000,s=abcdef123..." où s est un
// HMAC-SHA256 hex de "{timestamp}.{payload_brut}" avec le secret du webhook.
// Si jamais le format diffère de ta version de compte, le SDK officiel
// (Webhook::constructEvent) fait foi — voir composer require fedapay/fedapay-php.
function verifierSignatureFedapay(string $payload, string $signatureHeader, string $secret): bool
{
    $parties = [];
    foreach (explode(',', $signatureHeader) as $item) {
        [$cle, $valeur] = array_pad(explode('=', $item, 2), 2, null);
        $parties[$cle] = $valeur;
    }

    if (!isset($parties['t'], $parties['s'])) {
        return false;
    }

    $signatureAttendue = hash_hmac('sha256', $parties['t'] . '.' . $payload, $secret);

    // hash_equals : comparaison en temps constant, anti timing-attack
    return hash_equals($signatureAttendue, $parties['s']);
}

if (!verifierSignatureFedapay($payloadBrut, $signatureRecue, FEDAPAY_WEBHOOK_SECRET)) {
    http_response_code(400);
    logSecurityEvent('webhook_fedapay_signature_invalide', null, ['signature' => $signatureRecue]);
    exit('Signature invalide.');
}

// --- 3. Traitement de l'événement --------------------------------------------
$evenement = json_decode($payloadBrut, true);
$nomEvenement = $evenement['name'] ?? $evenement['event'] ?? null;
$transaction = $evenement['data']['object'] ?? $evenement['data'] ?? [];
$referenceInterne = $transaction['merchant_reference'] ?? null;

if (!$referenceInterne) {
    http_response_code(200); // on répond 200 quand même : événement non exploitable mais pas une erreur FedaPay
    exit('Événement ignoré (pas de référence marchande).');
}

$pdo = getPDO();

$statutsMap = [
    'transaction.approved'  => 'reussi',
    'transaction.declined'  => 'echoue',
    'transaction.canceled'  => 'echoue',
    'transaction.refunded'  => 'rembourse',
];

$nouveauStatut = $statutsMap[$nomEvenement] ?? null;

if ($nouveauStatut) {
    $stmt = $pdo->prepare('UPDATE dons SET statut = ? WHERE reference_transaction = ?');
    $stmt->execute([$nouveauStatut, $referenceInterne]);

    if ($stmt->rowCount() > 0) {
        logSecurityEvent('don_statut_maj_webhook', null, [
            'reference' => $referenceInterne, 'evenement' => $nomEvenement, 'nouveau_statut' => $nouveauStatut,
        ]);

        // Si succès : met à jour le montant collecté du projet + envoie le reçu par email
        if ($nouveauStatut === 'reussi') {
            $pdo->prepare(
                'UPDATE projets_patrimoine p
                 JOIN dons d ON d.projet_id = p.id
                 SET p.montant_collecte = p.montant_collecte + d.montant
                 WHERE d.reference_transaction = ?'
            )->execute([$referenceInterne]);

            // Récupère les infos complètes pour générer le reçu
            $donComplet = $pdo->prepare(
                'SELECT d.*, p.titre AS projet_titre, p.id AS projet_id
                 FROM dons d JOIN projets_patrimoine p ON p.id = d.projet_id
                 WHERE d.reference_transaction = ?'
            );
            $donComplet->execute([$referenceInterne]);
            $don = $donComplet->fetch();

            if ($don && !empty($don['donateur_email'])) {
                $recuHtml = genererRecuDonHtml($don, ['titre' => $don['projet_titre']]);
                $envoye = envoyerEmail(
                    $don['donateur_email'],
                    'Reçu de votre don — Bénin Vivant',
                    $recuHtml
                );
                logSecurityEvent('recu_don_envoye', null, [
                    'reference' => $referenceInterne,
                    'email' => $don['donateur_email'],
                    'succes' => $envoye,
                ]);
            }
        }
    }
}

// FedaPay considère le webhook comme livré uniquement sur un 200 — sinon il réessaie.
http_response_code(200);
echo json_encode(['received' => true]);
