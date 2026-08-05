<?php
/**
 * Création d'une fiche ethnie (Module 1).
 * Miroir de api/sites/create.php : n'existait pas jusqu'ici — l'admin ne
 * pouvait qu'approuver des contributions publiques, jamais ajouter une
 * nouvelle ethnie directement depuis le panneau d'administration.
 */
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
checkRateLimit('creer_ethnie', 10, 60);

$body = getJsonBody();

$nom = cleanString($body['nom'] ?? '', 100);
$regionPrincipale = cleanString($body['region_principale'] ?? '', 150);
$histoire = cleanString($body['histoire'] ?? '', 10000);
$languePrincipale = cleanString($body['langue_principale'] ?? '', 100);
$populationEstimee = cleanString($body['population_estimee'] ?? '', 50);
$imageCouverture = cleanString($body['image_couverture'] ?? '', 255);

// Publication immédiate uniquement si un admin/super_admin crée la fiche ;
// une contribution venant d'un simple compte reste en attente de validation,
// comme pour les sites.
$estAdmin = in_array($user['role'] ?? '', ['admin', 'super_admin'], true);

$errors = [];
if ($nom === '') $errors[] = "Le nom de l'ethnie est requis.";

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $nom), '-')) . '-' . uniqid();

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO groupes_ethniques
     (slug, nom, region_principale, histoire, langue_principale, population_estimee,
      image_couverture, is_published, created_by, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
);
$stmt->execute([
    $slug, $nom, $regionPrincipale, $histoire, $languePrincipale, $populationEstimee,
    $imageCouverture, $estAdmin ? 1 : 0, $user['id'],
]);

$newId = (int) $pdo->lastInsertId();
logSecurityEvent('ethnie_creee', $user['id'], ['ethnie_id' => $newId, 'publie_direct' => $estAdmin]);

jsonResponse([
    'message' => $estAdmin ? 'Ethnie créée et publiée.' : 'Ethnie soumise, en attente de validation par un modérateur.',
    'id' => $newId,
], 201);
