<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('contact_guide', 5, 60); // anti-spam

$body = getJsonBody();
$guideId = filter_var($body['guide_id'] ?? '', FILTER_VALIDATE_INT);
$nom = cleanString($body['visiteur_nom'] ?? '', 150);
$email = filter_var(trim($body['visiteur_email'] ?? ''), FILTER_VALIDATE_EMAIL);
$telephone = cleanString($body['visiteur_telephone'] ?? '', 20);
$message = cleanString($body['message'] ?? '', 2000);

$errors = [];
if (!$guideId) $errors[] = 'Guide invalide.';
if ($nom === '') $errors[] = 'Nom requis.';
if (!$email) $errors[] = 'Email invalide.';
if ($message === '') $errors[] = 'Message requis.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$pdo = getPDO();

$stmtGuide = $pdo->prepare("SELECT id FROM guides_touristiques WHERE id = ? AND statut = 'valide'");
$stmtGuide->execute([$guideId]);
if (!$stmtGuide->fetch()) {
    jsonError('Guide introuvable.', 404);
}

$stmt = $pdo->prepare(
    'INSERT INTO demandes_contact_guide (guide_id, visiteur_nom, visiteur_email, visiteur_telephone, message, statut, created_at)
     VALUES (?, ?, ?, ?, ?, "nouveau", NOW())'
);
$stmt->execute([$guideId, $nom, $email, $telephone ?: null, $message]);

logSecurityEvent('demande_contact_guide', currentUser()['id'] ?? null, ['guide_id' => $guideId]);

jsonResponse(['message' => 'Votre demande a bien été envoyée. Le guide vous contactera directement.'], 201);
