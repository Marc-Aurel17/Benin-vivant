<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonError('Méthode non autorisée.', 405);
}

$admin = requireRole('admin'); // seul un admin peut publier du contenu
requireCsrf();

$body = getJsonBody();
$id = isset($body['id']) ? (int) $body['id'] : 0;

if ($id <= 0) {
    jsonError('Identifiant invalide.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('UPDATE sites_historiques SET is_published = 1, updated_at = NOW() WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    jsonError('Site introuvable.', 404);
}

logSecurityEvent('site_publie', $admin['id'], ['site_id' => $id]);

jsonResponse(['message' => 'Site publié avec succès.']);
