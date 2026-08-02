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
$stmt = $pdo->query(
    'SELECT id, titre, slug, resume, source, image_couverture, publie_le
     FROM actualites
     WHERE publie_le IS NOT NULL AND publie_le <= NOW()
     ORDER BY publie_le DESC'
);

jsonResponse(['data' => $stmt->fetchAll()]);
