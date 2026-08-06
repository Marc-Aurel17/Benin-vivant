<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $langues = $pdo->query('SELECT * FROM langues ORDER BY nom')->fetchAll();
    $mots = $pdo->query('SELECT * FROM mots_langue ORDER BY id')->fetchAll();
    $parLangue = [];
    foreach ($mots as $m) { $parLangue[$m['langue_id']][] = $m; }
    foreach ($langues as &$l) { $l['mots'] = $parLangue[$l['id']] ?? []; }
    jsonResponse(['data' => $langues]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $body = getJsonBody();
    $action = $body['action'] ?? 'creer_langue';

    if ($action === 'ajouter_mot') {
        $langueId = (int) ($body['langue_id'] ?? 0);
        $mot = cleanString($body['mot_expression'] ?? '', 200);
        $traduction = cleanString($body['traduction_fr'] ?? '', 200);
        if ($langueId <= 0 || $mot === '' || $traduction === '') jsonError('Paramètres invalides.', 422);

        $pdo->prepare('INSERT INTO mots_langue (langue_id, mot_expression, traduction_fr) VALUES (?, ?, ?)')
            ->execute([$langueId, $mot, $traduction]);
        jsonResponse(['message' => 'Mot ajouté.'], 201);
    }

    $nom = cleanString($body['nom'] ?? '', 100);
    $zone = cleanString($body['zone_geographique'] ?? '', 200);
    $lat = isset($body['latitude_centre']) ? (float) $body['latitude_centre'] : null;
    $lng = isset($body['longitude_centre']) ? (float) $body['longitude_centre'] : null;
    $description = cleanString($body['description'] ?? '', 1000);

    if ($nom === '') jsonError('Nom de la langue requis.', 422);

    $pdo->prepare('INSERT INTO langues (nom, zone_geographique, latitude_centre, longitude_centre, description) VALUES (?, ?, ?, ?, ?)')
        ->execute([$nom, $zone, $lat, $lng, $description]);
    logSecurityEvent('langue_creee', $admin['id'], ['nom' => $nom]);
    jsonResponse(['message' => 'Langue ajoutée.'], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    $type = $body['type'] ?? 'langue';
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    if ($type === 'mot') {
        $mot = cleanString($body['mot_expression'] ?? '', 200);
        $traduction = cleanString($body['traduction_fr'] ?? '', 200);
        if ($mot === '' || $traduction === '') jsonError('Mot et traduction requis.', 422);
        $pdo->prepare('UPDATE mots_langue SET mot_expression = ?, traduction_fr = ? WHERE id = ?')
            ->execute([$mot, $traduction, $id]);
        jsonResponse(['message' => 'Mot modifié.']);
    }

    $nom = cleanString($body['nom'] ?? '', 100);
    $zone = cleanString($body['zone_geographique'] ?? '', 200);
    $lat = isset($body['latitude_centre']) ? (float) $body['latitude_centre'] : null;
    $lng = isset($body['longitude_centre']) ? (float) $body['longitude_centre'] : null;
    $description = cleanString($body['description'] ?? '', 1000);
    if ($nom === '') jsonError('Nom de la langue requis.', 422);

    $pdo->prepare('UPDATE langues SET nom = ?, zone_geographique = ?, latitude_centre = ?, longitude_centre = ?, description = ? WHERE id = ?')
        ->execute([$nom, $zone, $lat, $lng, $description, $id]);
    logSecurityEvent('langue_modifiee', $admin['id'], ['langue_id' => $id]);
    jsonResponse(['message' => 'Langue modifiée.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    $type = $body['type'] ?? 'langue';
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    if ($type === 'mot') {
        $pdo->prepare('DELETE FROM mots_langue WHERE id = ?')->execute([$id]);
        logSecurityEvent('mot_supprime', $admin['id'], ['mot_id' => $id]);
        jsonResponse(['message' => 'Mot supprimé.']);
    }

    $pdo->prepare('DELETE FROM langues WHERE id = ?')->execute([$id]); // CASCADE supprime les mots liés
    logSecurityEvent('langue_supprimee', $admin['id'], ['langue_id' => $id]);
    jsonResponse(['message' => 'Langue supprimée.']);
}

jsonError('Méthode non autorisée.', 405);
