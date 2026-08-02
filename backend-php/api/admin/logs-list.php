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

$limite = isset($_GET['limite']) ? min(100, max(1, (int) $_GET['limite'])) : 20;

$pdo = getPDO();
$stmt = $pdo->prepare(
    'SELECT l.action, l.ip_address, l.details, l.created_at, u.prenom, u.nom
     FROM audit_logs l LEFT JOIN users u ON u.id = l.user_id
     ORDER BY l.created_at DESC LIMIT ?'
);
$stmt->bindValue(1, $limite, PDO::PARAM_INT);
$stmt->execute();

jsonResponse(['data' => $stmt->fetchAll()]);
