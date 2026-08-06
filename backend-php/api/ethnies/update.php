<?php
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

$nom = cleanString($body['nom'] ?? '', 100);
$regionPrincipale = cleanString($body['region_principale'] ?? '', 150);
$histoire = cleanString($body['histoire'] ?? '', 10000);
$languePrincipale = cleanString($body['langue_principale'] ?? '', 100);
$populationEstimee = cleanString($body['population_estimee'] ?? '', 50);
$imageCouverture = cleanString($body['image_couverture'] ?? '', 255);

if ($nom === '') jsonError("Le nom de l'ethnie est requis.", 422);

$pdo = getPDO();
if ($imageCouverture !== '') {
    $pdo->prepare(
        'UPDATE groupes_ethniques SET nom = ?, region_principale = ?, histoire = ?, langue_principale = ?,
         population_estimee = ?, image_couverture = ?, updated_at = NOW() WHERE id = ?'
    )->execute([$nom, $regionPrincipale, $histoire, $languePrincipale, $populationEstimee, $imageCouverture, $id]);
} else {
    $pdo->prepare(
        'UPDATE groupes_ethniques SET nom = ?, region_principale = ?, histoire = ?, langue_principale = ?,
         population_estimee = ?, updated_at = NOW() WHERE id = ?'
    )->execute([$nom, $regionPrincipale, $histoire, $languePrincipale, $populationEstimee, $id]);
}

logSecurityEvent('ethnie_modifiee', $admin['id'], ['ethnie_id' => $id]);
jsonResponse(['message' => 'Ethnie modifiée.']);
