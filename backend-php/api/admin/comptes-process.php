<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$superAdmin = requireRole('super_admin');
requireCsrf();

$body = getJsonBody();
$id = isset($body['id']) ? (int) $body['id'] : 0;
$action = $body['action'] ?? ''; // 'activer' | 'desactiver' | 'supprimer'

if ($id <= 0 || !in_array($action, ['activer', 'desactiver', 'supprimer'], true)) {
    jsonError('Paramètres invalides.', 422);
}

// Un super_admin ne peut pas se désactiver/supprimer lui-même (évite de se
// verrouiller hors du panneau admin par erreur).
if ($id === $superAdmin['id']) {
    jsonError('Vous ne pouvez pas modifier votre propre compte depuis cette page.', 403);
}

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $action === 'supprimer') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin','super_admin')");
    $stmt->execute([$id]);
    $message = 'Compte supprimé.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $actif = $action === 'activer' ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role IN ('admin','super_admin')");
    $stmt->execute([$actif, $id]);
    $message = $actif ? 'Compte activé.' : 'Compte désactivé.';
} else {
    jsonError('Méthode non autorisée.', 405);
}

if ($stmt->rowCount() === 0) {
    jsonError('Compte introuvable.', 404);
}

logSecurityEvent('compte_admin_modifie', $superAdmin['id'], ['cible_id' => $id, 'action' => $action]);

jsonResponse(['message' => $message]);
