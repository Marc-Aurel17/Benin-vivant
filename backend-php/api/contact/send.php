<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('contact', 5, 60); // anti-spam

$body = getJsonBody();
$nom = cleanString($body['nom'] ?? '', 150);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$sujet = cleanString($body['sujet'] ?? '', 200);
$message = cleanString($body['message'] ?? '', 3000);

$errors = [];
if ($nom === '') $errors[] = 'Nom requis.';
if (!$email) $errors[] = 'Email invalide.';
if ($message === '') $errors[] = 'Message requis.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO contacts (nom, email, sujet, message, statut, created_at) VALUES (?, ?, ?, ?, "nouveau", NOW())'
);
$stmt->execute([$nom, $email, $sujet, $message]);

logSecurityEvent('contact_envoye', currentUser()['id'] ?? null, ['email' => $email]);

jsonResponse(['message' => 'Votre message a bien été envoyé. Nous vous répondrons rapidement.'], 201);
