<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = getPDO();
    $stmt = $pdo->query(
        "SELECT id, uuid, nom, prenom, email, is_active, last_login_at, created_at
         FROM users WHERE role = 'contributeur' ORDER BY created_at DESC"
    );
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    $action = $body['action'] ?? '';

    if ($id <= 0 || !in_array($action, ['activer', 'desactiver'], true)) {
        jsonError('Paramètres invalides.', 422);
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'contributeur'");
    $stmt->execute([$action === 'activer' ? 1 : 0, $id]);

    if ($stmt->rowCount() === 0) {
        jsonError('Contributeur introuvable.', 404);
    }

    logSecurityEvent('contributeur_statut_modifie', $admin['id'], ['user_id' => $id, 'action' => $action]);
    jsonResponse(['message' => 'Statut mis à jour.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) {
        jsonError('Identifiant invalide.', 422);
    }
    $pdo = getPDO();
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'contributeur'")->execute([$id]);
    logSecurityEvent('contributeur_supprime', $admin['id'], ['user_id' => $id]);
    jsonResponse(['message' => 'Compte supprimé.']);
}

jsonError('Méthode non autorisée.', 405);
