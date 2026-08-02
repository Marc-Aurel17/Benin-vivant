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
    'SELECT a.*, u.prenom AS auteur_prenom, u.nom AS auteur_nom
     FROM actualites a LEFT JOIN users u ON u.id = a.auteur_id
     WHERE a.slug = ? AND a.publie_le IS NOT NULL AND a.publie_le <= NOW()'
);
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    jsonError('Article introuvable.', 404);
}

$autres = $pdo->prepare(
    'SELECT titre, slug, resume, source, publie_le FROM actualites
     WHERE slug != ? AND publie_le IS NOT NULL AND publie_le <= NOW()
     ORDER BY publie_le DESC LIMIT 3'
);
$autres->execute([$slug]);

jsonResponse(['data' => ['article' => $article, 'a_lire_aussi' => $autres->fetchAll()]]);
