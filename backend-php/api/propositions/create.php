<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('proposition_projet', 5, 300);

$body = getJsonBody();
$nomPorteur = cleanString($body['nom_porteur'] ?? '', 200);
$emailContact = filter_var(trim($body['email_contact'] ?? ''), FILTER_VALIDATE_EMAIL);
$titre = cleanString($body['titre'] ?? '', 200);
$description = cleanString($body['description'] ?? '', 3000);
$typeProjet = $body['type_projet'] ?? '';
$typesAutorises = ['restauration', 'collecte_recits', 'numerisation_archives', 'initiative_scolaire'];

$errors = [];
if ($nomPorteur === '') $errors[] = 'Nom du porteur de projet requis.';
if (!$emailContact) $errors[] = 'Email invalide.';
if ($titre === '') $errors[] = 'Titre requis.';
if ($description === '') $errors[] = 'Description requise.';
if (!in_array($typeProjet, $typesAutorises, true)) $errors[] = 'Type de projet invalide.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO propositions_projets (nom_porteur, email_contact, titre, description, type_projet, statut, created_at)
     VALUES (?, ?, ?, ?, ?, "nouveau", NOW())'
);
$stmt->execute([$nomPorteur, $emailContact, $titre, $description, $typeProjet]);

logSecurityEvent('proposition_soumise', currentUser()['id'] ?? null, ['titre' => $titre]);

jsonResponse(['message' => 'Proposition envoyée, notre équipe l\'examinera prochainement.'], 201);
