<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

requireRole('admin', 'super_admin');

$pdo = getPDO();

$sites = $pdo->query(
    "SELECT s.id, s.nom, s.ville, s.latitude, s.longitude, s.is_published, s.created_at,
            u.prenom AS createur_prenom, u.nom AS createur_nom
     FROM sites_historiques s LEFT JOIN users u ON u.id = s.created_by
     ORDER BY s.is_published ASC, s.created_at DESC"
)->fetchAll();

$ethnies = $pdo->query(
    "SELECT e.id, e.nom, e.region_principale, e.is_published, e.created_at,
            u.prenom AS createur_prenom, u.nom AS createur_nom
     FROM groupes_ethniques e LEFT JOIN users u ON u.id = e.created_by
     ORDER BY e.is_published ASC, e.created_at DESC"
)->fetchAll();

jsonResponse(['data' => ['sites' => $sites, 'ethnies' => $ethnies]]);
