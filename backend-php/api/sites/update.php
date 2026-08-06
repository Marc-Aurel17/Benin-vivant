<?php
/**
 * Modification d'un site historique existant. Réservé aux admins/modérateurs
 * (contrairement à create.php qui accepte aussi les contributeurs).
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonError('Méthode non autorisée.', 405);
}

$admin = requireRole('admin', 'super_admin');
requireCsrf();

$body = getJsonBody();
$id = isset($body['id']) ? (int) $body['id'] : 0;
if ($id <= 0) jsonError('Identifiant invalide.', 422);

$nom = cleanString($body['nom'] ?? '', 200);
$description = cleanString($body['description'] ?? '', 5000);
$histoire = cleanString($body['histoire'] ?? '', 10000);
$ville = cleanString($body['ville'] ?? '', 100);
$departement = cleanString($body['departement'] ?? '', 100);
$lat = validateFloat($body['latitude'] ?? null, -90, 90);
$lng = validateFloat($body['longitude'] ?? null, -180, 180);
$duree = isset($body['duree_visite_recommandee_min']) ? (int) $body['duree_visite_recommandee_min'] : null;
$imageUrl = cleanString($body['image_url'] ?? '', 255);

$errors = [];
if ($nom === '') $errors[] = 'Le nom du site est requis.';
if ($lat === null) $errors[] = 'Latitude invalide.';
if ($lng === null) $errors[] = 'Longitude invalide.';
if ($errors) jsonError(implode(' ', $errors), 422);

$pdo = getPDO();
$pdo->prepare(
    'UPDATE sites_historiques SET nom = ?, description = ?, histoire = ?, ville = ?, departement = ?,
     latitude = ?, longitude = ?, duree_visite_recommandee_min = ?, updated_at = NOW() WHERE id = ?'
)->execute([$nom, $description, $histoire, $ville, $departement, $lat, $lng, $duree, $id]);

if ($imageUrl !== '') {
    $existe = $pdo->prepare("SELECT id FROM medias WHERE mediable_type = 'site_historique' AND mediable_id = ? LIMIT 1");
    $existe->execute([$id]);
    if ($existe->fetch()) {
        $pdo->prepare("UPDATE medias SET url = ? WHERE mediable_type = 'site_historique' AND mediable_id = ?")->execute([$imageUrl, $id]);
    } else {
        $pdo->prepare("INSERT INTO medias (mediable_type, mediable_id, type, url, created_at) VALUES ('site_historique', ?, 'image', ?, NOW())")->execute([$id, $imageUrl]);
    }
}

logSecurityEvent('site_modifie', $admin['id'], ['site_id' => $id]);
jsonResponse(['message' => 'Site modifié.']);
