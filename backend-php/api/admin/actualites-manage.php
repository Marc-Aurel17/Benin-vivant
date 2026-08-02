<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM actualites ORDER BY created_at DESC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $body = getJsonBody();
    $titre = cleanString($body['titre'] ?? '', 200);
    $resume = cleanString($body['resume'] ?? '', 500);
    $contenu = cleanString($body['contenu'] ?? '', 10000);
    $source = $body['source'] ?? 'interne';
    $publier = !empty($body['publier']);

    if ($titre === '' || $contenu === '') {
        jsonError('Titre et contenu requis.', 422);
    }
    if (!in_array($source, ['interne', 'unesco', 'officiel'], true)) {
        jsonError('Source invalide.', 422);
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $titre), '-')) . '-' . uniqid();

    $stmt = $pdo->prepare(
        'INSERT INTO actualites (titre, slug, resume, contenu, source, auteur_id, publie_le, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$titre, $slug, $resume, $contenu, $source, $admin['id'], $publier ? date('Y-m-d H:i:s') : null]);

    logSecurityEvent('actualite_creee', $admin['id'], ['titre' => $titre]);
    jsonResponse(['message' => 'Actualité créée.', 'id' => (int) $pdo->lastInsertId()], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) {
        jsonError('Identifiant invalide.', 422);
    }

    if (isset($body['action']) && $body['action'] === 'toggle_publication') {
        $current = $pdo->prepare('SELECT publie_le FROM actualites WHERE id = ?');
        $current->execute([$id]);
        $row = $current->fetch();
        if (!$row) jsonError('Actualité introuvable.', 404);

        $nouvelleValeur = $row['publie_le'] ? null : date('Y-m-d H:i:s');
        $pdo->prepare('UPDATE actualites SET publie_le = ? WHERE id = ?')->execute([$nouvelleValeur, $id]);
        jsonResponse(['message' => $nouvelleValeur ? 'Publiée.' : 'Dépubliée.']);
    }

    $champs = [];
    $valeurs = [];
    foreach (['titre', 'resume', 'contenu', 'source'] as $champ) {
        if (isset($body[$champ])) {
            $champs[] = "$champ = ?";
            $valeurs[] = cleanString($body[$champ], $champ === 'contenu' ? 10000 : 500);
        }
    }
    if (empty($champs)) jsonError('Aucune modification fournie.', 422);

    $valeurs[] = $id;
    $pdo->prepare('UPDATE actualites SET ' . implode(', ', $champs) . ' WHERE id = ?')->execute($valeurs);
    logSecurityEvent('actualite_modifiee', $admin['id'], ['actualite_id' => $id]);
    jsonResponse(['message' => 'Actualité mise à jour.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    $pdo->prepare('DELETE FROM actualites WHERE id = ?')->execute([$id]);
    logSecurityEvent('actualite_supprimee', $admin['id'], ['actualite_id' => $id]);
    jsonResponse(['message' => 'Actualité supprimée.']);
}

jsonError('Méthode non autorisée.', 405);
