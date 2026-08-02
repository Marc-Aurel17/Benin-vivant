<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('newsletter', 5, 60);

$body = getJsonBody();
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email) {
    jsonError('Adresse email invalide.', 422);
}

$pdo = getPDO();
// Ré-abonnement silencieux si déjà présent mais désactivé
$stmt = $pdo->prepare(
    'INSERT INTO newsletter_abonnes (email, actif, created_at) VALUES (?, 1, NOW())
     ON DUPLICATE KEY UPDATE actif = 1'
);
$stmt->execute([$email]);

jsonResponse(['message' => 'Merci ! Vous êtes abonné aux actualités du patrimoine béninois.'], 201);
