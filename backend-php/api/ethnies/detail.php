<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$slug = cleanString($_GET['slug'] ?? '', 120);
if ($slug === '') {
    jsonError('Paramètre slug requis.', 422);
}

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT * FROM groupes_ethniques WHERE slug = ? AND is_published = 1');
$stmt->execute([$slug]);
$ethnie = $stmt->fetch();

// 404 générique, qu'il s'agisse d'un slug inexistant ou d'un contenu non publié
// (on ne révèle jamais l'existence d'un contenu en modération).
if (!$ethnie) {
    jsonError('Fiche introuvable.', 404);
}

$id = $ethnie['id'];

$religions = $pdo->prepare('SELECT titre, fetiche_divinite, description FROM religions_traditions WHERE groupe_ethnique_id = ?');
$religions->execute([$id]);

$plats = $pdo->prepare('SELECT nom, description, ingredients FROM plats_traditionnels WHERE groupe_ethnique_id = ?');
$plats->execute([$id]);

$danses = $pdo->prepare('SELECT nom, contexte_pratique, description FROM danses_traditionnelles WHERE groupe_ethnique_id = ?');
$danses->execute([$id]);

$tenues = $pdo->prepare('SELECT nom, description, occasion_usage FROM tenues_traditionnelles WHERE groupe_ethnique_id = ?');
$tenues->execute([$id]);

$objets = $pdo->prepare('SELECT nom, signification FROM objets_art WHERE groupe_ethnique_id = ?');
$objets->execute([$id]);

$medias = $pdo->prepare('SELECT type, url, legende FROM medias WHERE mediable_type = "groupe_ethnique" AND mediable_id = ? ORDER BY ordre');
$medias->execute([$id]);

jsonResponse(['data' => [
    'ethnie' => $ethnie,
    'religions_traditions' => $religions->fetchAll(),
    'plats_traditionnels' => $plats->fetchAll(),
    'danses_traditionnelles' => $danses->fetchAll(),
    'tenues_traditionnelles' => $tenues->fetchAll(),
    'objets_art' => $objets->fetchAll(),
    'medias' => $medias->fetchAll(),
]]);
