<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('demande_admin_start', 5, 300); // 5 par 5 minutes : anti-spam de demandes

$body = getJsonBody();
$nom = cleanString($body['nom'] ?? '', 100);
$prenom = cleanString($body['prenom'] ?? '', 100);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$telephone = cleanString($body['telephone'] ?? '', 20);

$errors = [];
if ($nom === '') $errors[] = 'Nom requis.';
if ($prenom === '') $errors[] = 'Prénom requis.';
if (!$email) $errors[] = 'Email invalide.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$pdo = getPDO();

// Un email ne peut avoir qu'une demande active à la fois
$check = $pdo->prepare("SELECT id, statut FROM demandes_inscription_admin WHERE email = ? AND statut NOT IN ('rejete','bloque') ORDER BY id DESC LIMIT 1");
$check->execute([$email]);
if ($existing = $check->fetch()) {
    jsonError('Une demande est déjà en cours pour cet email (statut : ' . $existing['statut'] . ').', 409);
}

// Code à 6 chiffres, générateur cryptographiquement sûr (pas mt_rand)
$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$codeHash = password_hash($code, PASSWORD_BCRYPT); // le code est hashé, jamais stocké en clair

$stmt = $pdo->prepare(
    'INSERT INTO demandes_inscription_admin
     (nom, prenom, email, telephone, code_verification_email, statut, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, "etape_email", NOW(), NOW())'
);
$stmt->execute([$nom, $prenom, $email, $telephone, $codeHash]);

$demandeId = (int) $pdo->lastInsertId();

// TODO production : envoyer réellement $code par email (jamais dans la réponse JSON).
// En local/démo uniquement, on peut le logger côté serveur pour tester :
error_log("[DEMO] Code de vérification pour demande #{$demandeId} : {$code}");

logSecurityEvent('demande_admin_etape1', null, ['demande_id' => $demandeId, 'email' => $email]);

jsonResponse([
    'message' => 'Demande initiée. Un code de vérification a été envoyé par email.',
    'demande_id' => $demandeId,
], 201);
