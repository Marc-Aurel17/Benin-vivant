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
$stmt = $pdo->query(
    'SELECT id, type_probleme, titre, description, latitude, longitude, statut, created_at
     FROM signalements ORDER BY FIELD(statut, \'nouveau\', \'en_cours\', \'resolu\', \'rejete\'), created_at DESC'
);

jsonResponse(['data' => $stmt->fetchAll()]);
