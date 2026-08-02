<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$slug = cleanString($_GET['slug'] ?? '', 220);
if ($slug === '') {
    jsonError('Paramètre slug requis.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'SELECT e.*, g.nom AS ethnie_nom, g.slug AS ethnie_slug,
            s.nom AS site_nom, s.slug AS site_slug
     FROM evenements e
     LEFT JOIN groupes_ethniques g ON g.id = e.groupe_ethnique_id
     LEFT JOIN sites_historiques s ON s.id = e.site_historique_id
     WHERE e.slug = ? AND e.is_published = 1'
);
$stmt->execute([$slug]);
$evenement = $stmt->fetch();

if (!$evenement) {
    jsonError('Événement introuvable.', 404);
}

$medias = $pdo->prepare('SELECT type, url, legende FROM medias WHERE mediable_type = "evenement" AND mediable_id = ? ORDER BY ordre');
$medias->execute([$evenement['id']]);

$countInteresses = $pdo->prepare('SELECT COUNT(*) c FROM evenement_interesses WHERE evenement_id = ?');
$countInteresses->execute([$evenement['id']]);

jsonResponse(['data' => [
    'evenement' => $evenement,
    'medias' => $medias->fetchAll(),
    'nombre_interesses' => (int) $countInteresses->fetch()['c'],
]]);
