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
$imageUrl = cleanString($body['image_url'] ?? '', 255); // optionnel — voir table medias (polymorphe)

$errors = [];
if ($nom === '') $errors[] = 'Le nom du site est requis.';
if ($lat === null) $errors[] = 'Latitude invalide (doit être entre -90 et 90).';
if ($lng === null) $errors[] = 'Longitude invalide (doit être entre -180 et 180).';
if ($duree !== null && ($duree < 0 || $duree > 1440)) $errors[] = 'Durée de visite invalide.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $nom), '-')) . '-' . uniqid();

// Publication immédiate si un admin/super_admin crée la fiche ; une
// contribution venant d'un simple compte reste en attente de validation.
$estAdmin = in_array($user['role'] ?? '', ['admin', 'super_admin'], true);

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO sites_historiques
     (slug, nom, description, histoire, latitude, longitude, ville, departement,
      duree_visite_recommandee_min, is_published, created_by, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
);
$stmt->execute([$slug, $nom, $description, $histoire, $lat, $lng, $ville, $departement, $duree, $estAdmin ? 1 : 0, $user['id']]);

$newId = (int) $pdo->lastInsertId();

if ($imageUrl !== '') {
    $pdo->prepare(
        "INSERT INTO medias (mediable_type, mediable_id, type, url, created_at) VALUES ('site_historique', ?, 'image', ?, NOW())"
    )->execute([$newId, $imageUrl]);
}

logSecurityEvent('site_cree', $user['id'], ['site_id' => $newId, 'publie_direct' => $estAdmin]);

jsonResponse([
    'message' => $estAdmin ? 'Site créé et publié.' : 'Site soumis, en attente de validation par un modérateur.',
    'id' => $newId,
], 201);
