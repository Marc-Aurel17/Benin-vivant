<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonError('Méthode non autorisée.', 405);
}

$admin = requireRole('admin', 'super_admin');
requireCsrf();

$body = getJsonBody();
$id = isset($body['id']) ? (int) $body['id'] : 0;
if ($id <= 0) jsonError('Identifiant invalide.', 422);

$pdo = getPDO();
$pdo->prepare('DELETE FROM evenements WHERE id = ?')->execute([$id]);

logSecurityEvent('evenement_supprime', $admin['id'], ['evenement_id' => $id]);
jsonResponse(['message' => 'Événement supprimé.']);
