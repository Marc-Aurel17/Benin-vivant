<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$user = requireAuth();      // doit être connecté
requireCsrf();               // jeton CSRF valide
checkRateLimit('creer_site', 10, 60);

$body = getJsonBody();

$nom = cleanString($body['nom'] ?? '', 200);
$description = cleanString($body['description'] ?? '', 5000);
$histoire = cleanString($body['histoire'] ?? '', 10000);
$ville = cleanString($body['ville'] ?? '', 100);
$departement = cleanString($body['departement'] ?? '', 100);
$lat = validateFloat($body['latitude'] ?? null, -90, 90);
$lng = validateFloat($body['longitude'] ?? null, -180, 180);
$duree = isset($body['duree_visite_recommandee_min']) ? (int) $body['duree_visite_recommandee_min'] : null;

$errors = [];
if ($nom === '') $errors[] = 'Le nom du site est requis.';
if ($lat === null) $errors[] = 'Latitude invalide (doit être entre -90 et 90).';
if ($lng === null) $errors[] = 'Longitude invalide (doit être entre -180 et 180).';
if ($duree !== null && ($duree < 0 || $duree > 1440)) $errors[] = 'Durée de visite invalide.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $nom), '-')) . '-' . uniqid();

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO sites_historiques
     (slug, nom, description, histoire, latitude, longitude, ville, departement,
      duree_visite_recommandee_min, is_published, created_by, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())'
);
// is_published toujours à 0 : validation obligatoire par un admin/modérateur avant publication
$stmt->execute([$slug, $nom, $description, $histoire, $lat, $lng, $ville, $departement, $duree, $user['id']]);

$newId = (int) $pdo->lastInsertId();
logSecurityEvent('site_cree', $user['id'], ['site_id' => $newId]);

jsonResponse(['message' => 'Site soumis, en attente de validation par un modérateur.', 'id' => $newId], 201);
