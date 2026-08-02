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
$action = $body['action'] ?? ''; // 'valider' | 'rejeter'
$commentaire = cleanString($body['commentaire'] ?? '', 500);

if ($id <= 0 || !in_array($action, ['valider', 'rejeter'], true)) {
    jsonError('Paramètres invalides.', 422);
}

$nouveauStatut = $action === 'valider' ? 'valide' : 'rejete';

$pdo = getPDO();
$stmt = $pdo->prepare(
    'UPDATE contributions_utilisateurs
     SET statut = ?, moderateur_id = ?, commentaire_moderation = ?
     WHERE id = ?'
);
$stmt->execute([$nouveauStatut, $admin['id'], $commentaire, $id]);

if ($stmt->rowCount() === 0) {
    jsonError('Contribution introuvable.', 404);
}

logSecurityEvent('contribution_moderee', $admin['id'], ['contribution_id' => $id, 'action' => $action]);

jsonResponse(['message' => 'Contribution ' . ($action === 'valider' ? 'validée' : 'rejetée') . '.']);
