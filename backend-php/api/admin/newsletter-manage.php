<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

requireRole('admin', 'super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, email, actif, created_at FROM newsletter_abonnes ORDER BY created_at DESC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) {
        jsonError('Identifiant invalide.', 422);
    }
    $pdo = getPDO();
    $pdo->prepare('DELETE FROM newsletter_abonnes WHERE id = ?')->execute([$id]);
    jsonResponse(['message' => 'Abonné supprimé.']);
}

jsonError('Méthode non autorisée.', 405);
