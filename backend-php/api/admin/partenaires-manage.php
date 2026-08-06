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
    $logoUrl = cleanString($body['logo_url'] ?? '', 255);

    if ($nom === '') jsonError('Nom requis.', 422);

    $stmt = $pdo->prepare('INSERT INTO partenaires (nom, logo_url, site_web, ordre, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$nom, $logoUrl ?: null, $siteWeb ?: null, $ordre]);
    logSecurityEvent('partenaire_cree', $admin['id'], ['nom' => $nom]);
    jsonResponse(['message' => 'Partenaire ajouté.'], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    $nom = cleanString($body['nom'] ?? '', 150);
    $siteWeb = cleanString($body['site_web'] ?? '', 255);
    $ordre = isset($body['ordre']) ? (int) $body['ordre'] : 0;
    $logoUrl = cleanString($body['logo_url'] ?? '', 255);
    if ($nom === '') jsonError('Nom requis.', 422);

    $pdo->prepare('UPDATE partenaires SET nom = ?, logo_url = ?, site_web = ?, ordre = ? WHERE id = ?')
        ->execute([$nom, $logoUrl ?: null, $siteWeb ?: null, $ordre, $id]);
    logSecurityEvent('partenaire_modifie', $admin['id'], ['partenaire_id' => $id]);
    jsonResponse(['message' => 'Partenaire modifié.']);
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
