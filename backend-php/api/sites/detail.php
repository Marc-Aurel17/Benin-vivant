<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$slug = cleanString($_GET['slug'] ?? '', 150);
if ($slug === '') {
    jsonError('Paramètre slug requis.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM sites_historiques WHERE slug = ? AND is_published = 1');
$stmt->execute([$slug]);
$site = $stmt->fetch();

if (!$site) {
    jsonError('Site introuvable.', 404);
}

$medias = $pdo->prepare('SELECT type, url, legende FROM medias WHERE mediable_type = "site_historique" AND mediable_id = ? ORDER BY ordre');
$medias->execute([$site['id']]);

jsonResponse(['data' => [
    'site' => $site,
    'medias' => $medias->fetchAll(),
]]);
