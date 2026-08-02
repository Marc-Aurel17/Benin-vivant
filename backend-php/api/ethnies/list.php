<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$pdo = getPDO();

// On ne renvoie jamais le contenu non publié (en attente de modération) au public.
$stmt = $pdo->query(
    'SELECT id, slug, nom, region_principale, langue_principale, image_couverture
     FROM groupes_ethniques
     WHERE is_published = 1
     ORDER BY nom'
);

jsonResponse(['data' => $stmt->fetchAll()]);
