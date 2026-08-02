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
    "SELECT id, uuid, nom, prenom, email, role, is_active, last_login_at, created_at
     FROM users WHERE role IN ('admin', 'super_admin') ORDER BY role DESC, created_at ASC"
);

jsonResponse(['data' => $stmt->fetchAll()]);
