<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$user = requireAuth();
requireCsrf();
checkRateLimit('creer_contribution', 10, 60);

$body = getJsonBody();
$type = $body['type_contribution'] ?? '';
$titre = cleanString($body['titre'] ?? '', 200);
$contenu = cleanString($body['contenu'] ?? '', 5000);

$typesAutorises = ['tradition', 'site', 'information'];
$errors = [];
if (!in_array($type, $typesAutorises, true)) $errors[] = 'Type de contribution invalide.';
if ($titre === '') $errors[] = 'Titre requis.';
if ($contenu === '') $errors[] = 'Contenu requis.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO contributions_utilisateurs (user_id, type_contribution, titre, contenu, statut, created_at)
     VALUES (?, ?, ?, ?, \'en_attente\', NOW())'
);
$stmt->execute([$user['id'], $type, $titre, $contenu]);

logSecurityEvent('contribution_creee', $user['id'], ['type' => $type, 'titre' => $titre]);

jsonResponse(['message' => 'Contribution soumise avec succès, en attente de validation par un modérateur.'], 201);
