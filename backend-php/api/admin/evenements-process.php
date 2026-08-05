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
$action = $body['action'] ?? ''; // 'publier' | 'rejeter' | 'annuler'

if ($id <= 0 || !in_array($action, ['publier', 'rejeter', 'annuler'], true)) {
    jsonError('Paramètres invalides.', 422);
}

$pdo = getPDO();

if ($action === 'publier') {
    $stmt = $pdo->prepare('UPDATE evenements SET is_published = 1, updated_at = NOW() WHERE id = ?');
} elseif ($action === 'annuler') {
    $stmt = $pdo->prepare('UPDATE evenements SET statut = \'annule\', updated_at = NOW() WHERE id = ?');
} else {
    $stmt = $pdo->prepare('DELETE FROM evenements WHERE id = ?');
}
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    jsonError('Événement introuvable.', 404);
}

logSecurityEvent('evenement_traite', $admin['id'], ['evenement_id' => $id, 'action' => $action]);

jsonResponse(['message' => 'Événement traité avec succès.']);
