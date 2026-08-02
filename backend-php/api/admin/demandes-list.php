<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

requireRole('super_admin');

$pdo = getPDO();
$stmt = $pdo->query(
    "SELECT id, nom, prenom, email, telephone, statut, piece_identite_url, created_at
     FROM demandes_inscription_admin
     WHERE statut IN ('en_attente_validation','approuve','rejete','bloque')
     ORDER BY created_at DESC"
);

jsonResponse(['data' => $stmt->fetchAll()]);
