<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonError('Méthode non autorisée.', 405);
}

$admin = requireRole('admin', 'super_admin');
requireCsrf();

$body = getJsonBody();
$id = isset($body['id']) ? (int) $body['id'] : 0;
$statut = $body['statut'] ?? '';
$statutsAutorises = ['nouveau', 'en_cours', 'resolu', 'rejete'];

if ($id <= 0 || !in_array($statut, $statutsAutorises, true)) {
    jsonError('Paramètres invalides.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('UPDATE signalements SET statut = ?, updated_at = NOW() WHERE id = ?');
$stmt->execute([$statut, $id]);

if ($stmt->rowCount() === 0) {
    jsonError('Signalement introuvable.', 404);
}

logSecurityEvent('signalement_traite', $admin['id'], ['signalement_id' => $id, 'nouveau_statut' => $statut]);

jsonResponse(['message' => 'Statut mis à jour.']);
