<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM partenaires ORDER BY ordre ASC, nom ASC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $body = getJsonBody();
    $nom = cleanString($body['nom'] ?? '', 150);
    $siteWeb = cleanString($body['site_web'] ?? '', 255);
    $ordre = isset($body['ordre']) ? (int) $body['ordre'] : 0;

    if ($nom === '') jsonError('Nom requis.', 422);

    $stmt = $pdo->prepare('INSERT INTO partenaires (nom, site_web, ordre, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$nom, $siteWeb ?: null, $ordre]);
    logSecurityEvent('partenaire_cree', $admin['id'], ['nom' => $nom]);
    jsonResponse(['message' => 'Partenaire ajouté.'], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    $pdo->prepare('DELETE FROM partenaires WHERE id = ?')->execute([$id]);
    logSecurityEvent('partenaire_supprime', $admin['id'], ['partenaire_id' => $id]);
    jsonResponse(['message' => 'Partenaire supprimé.']);
}

jsonError('Méthode non autorisée.', 405);
