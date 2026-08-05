<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// Anti brute-force sur le code à 6 chiffres (10^6 combinaisons, throttle indispensable)
checkRateLimit('demande_admin_verify', 8, 300);

$body = getJsonBody();
$demandeId = isset($body['demande_id']) ? (int) $body['demande_id'] : 0;
$code = (string) ($body['code'] ?? '');

if ($demandeId <= 0 || $code === '') {
    jsonError('Identifiant de demande et code requis.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM demandes_inscription_admin WHERE id = ? AND statut = \'etape_email\'');
$stmt->execute([$demandeId]);
$demande = $stmt->fetch();

if (!$demande) {
    jsonError('Demande introuvable ou déjà validée.', 404);
}

if (!password_verify($code, $demande['code_verification_email'])) {
    logSecurityEvent('demande_admin_code_invalide', null, ['demande_id' => $demandeId]);
    jsonError('Code de vérification incorrect.', 401);
}

$update = $pdo->prepare(
    'UPDATE demandes_inscription_admin
     SET email_verifie = 1, statut = \'etape_identite\', updated_at = NOW()
     WHERE id = ?'
);
$update->execute([$demandeId]);

jsonResponse(['message' => 'Email vérifié. Passez à l\'étape de vérification d\'identité.']);
