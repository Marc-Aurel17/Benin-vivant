<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM temoignages ORDER BY created_at DESC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    $current = $pdo->prepare('SELECT is_published FROM temoignages WHERE id = ?');
    $current->execute([$id]);
    $row = $current->fetch();
    if (!$row) jsonError('Témoignage introuvable.', 404);

    $pdo->prepare('UPDATE temoignages SET is_published = ? WHERE id = ?')->execute([$row['is_published'] ? 0 : 1, $id]);
    logSecurityEvent('temoignage_modere', $admin['id'], ['temoignage_id' => $id]);
    jsonResponse(['message' => $row['is_published'] ? 'Dépublié.' : 'Publié.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    $pdo->prepare('DELETE FROM temoignages WHERE id = ?')->execute([$id]);
    logSecurityEvent('temoignage_supprime', $admin['id'], ['temoignage_id' => $id]);
    jsonResponse(['message' => 'Témoignage supprimé.']);
}

jsonError('Méthode non autorisée.', 405);
