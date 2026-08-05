<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// Accessible sans compte, donc throttle serré (anti-spam) : 10 requêtes/min/IP
checkRateLimit('signalement', 10, 60);

$body = getJsonBody();

$typesAutorises = ['site_degrade', 'monument_menace', 'tradition_en_danger', 'erreur_contenu'];
$type = $body['type_probleme'] ?? '';
$titre = cleanString($body['titre'] ?? '', 200);
$description = cleanString($body['description'] ?? '', 3000);
$lat = isset($body['latitude']) ? validateFloat($body['latitude'], -90, 90) : null;
$lng = isset($body['longitude']) ? validateFloat($body['longitude'], -180, 180) : null;

$errors = [];
if (!in_array($type, $typesAutorises, true)) $errors[] = 'Type de problème invalide.';
if ($titre === '') $errors[] = 'Le titre est requis.';
if ($description === '') $errors[] = 'La description est requise.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$user = currentUser(); // peut être null (visiteur non connecté)

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO signalements (user_id, type_probleme, titre, description, latitude, longitude, statut, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, \'nouveau\', NOW(), NOW())'
);
$stmt->execute([$user['id'] ?? null, $type, $titre, $description, $lat, $lng]);

logSecurityEvent('signalement_cree', $user['id'] ?? null, ['type' => $type]);

jsonResponse(['message' => 'Signalement enregistré, merci pour votre contribution à la préservation du patrimoine.'], 201);
