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

$periodes = $pdo->query(
    'SELECT titre, categorie, date_debut, date_fin, description, image_avant, image_apres
     FROM periode_evolution_benin ORDER BY ordre_frise ASC'
)->fetchAll();

$figures = $pdo->query(
    'SELECT nom, periode, biographie, portrait_url FROM figures_historiques ORDER BY id ASC'
)->fetchAll();

jsonResponse(['data' => ['frise' => $periodes, 'figures' => $figures]]);
