<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

requireRole('admin', 'super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, nom, email, sujet, message, statut, created_at FROM contacts ORDER BY created_at DESC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) {
        jsonError('Identifiant invalide.', 422);
    }
    $pdo = getPDO();
    $pdo->prepare('UPDATE contacts SET statut = \'lu\' WHERE id = ?')->execute([$id]);
    jsonResponse(['message' => 'Marqué comme lu.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) {
        jsonError('Identifiant invalide.', 422);
    }
    $pdo = getPDO();
    $pdo->prepare('DELETE FROM contacts WHERE id = ?')->execute([$id]);
    jsonResponse(['message' => 'Message supprimé.']);
}

jsonError('Méthode non autorisée.', 405);
