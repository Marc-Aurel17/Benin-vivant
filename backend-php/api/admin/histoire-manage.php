<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $periodes = $pdo->query('SELECT * FROM periode_evolution_benin ORDER BY ordre_frise')->fetchAll();
    $figures = $pdo->query('SELECT * FROM figures_historiques ORDER BY id')->fetchAll();
    jsonResponse(['data' => ['periodes' => $periodes, 'figures' => $figures]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $body = getJsonBody();
    $type = $body['type'] ?? ''; // 'periode' | 'figure'

    if ($type === 'figure') {
        $nom = cleanString($body['nom'] ?? '', 150);
        $periode = cleanString($body['periode'] ?? '', 100);
        $bio = cleanString($body['biographie'] ?? '', 3000);
        $portraitUrl = cleanString($body['portrait_url'] ?? '', 255);
        if ($nom === '') jsonError('Nom requis.', 422);

        $pdo->prepare('INSERT INTO figures_historiques (nom, periode, biographie, portrait_url, created_at) VALUES (?, ?, ?, ?, NOW())')
            ->execute([$nom, $periode, $bio, $portraitUrl ?: null]);
        jsonResponse(['message' => 'Figure historique ajoutée.'], 201);
    }

    $titre = cleanString($body['titre'] ?? '', 200);
    $categorie = $body['categorie'] ?? '';
    $dateDebut = isset($body['date_debut']) ? (int) $body['date_debut'] : null;
    $dateFin = isset($body['date_fin']) && $body['date_fin'] !== '' ? (int) $body['date_fin'] : null;
    $description = cleanString($body['description'] ?? '', 2000);
    $ordre = isset($body['ordre_frise']) ? (int) $body['ordre_frise'] : 0;
    $imageAvant = cleanString($body['image_avant'] ?? '', 255);
    $imageApres = cleanString($body['image_apres'] ?? '', 255);

    $categoriesAutorisees = ['royaume_precolonial', 'colonisation', 'esclavage', 'independance', 'moderne'];
    if ($titre === '' || !in_array($categorie, $categoriesAutorisees, true)) {
        jsonError('Titre et catégorie valide requis.', 422);
    }

    $pdo->prepare(
        'INSERT INTO periode_evolution_benin (titre, categorie, date_debut, date_fin, description, image_avant, image_apres, ordre_frise, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    )->execute([$titre, $categorie, $dateDebut, $dateFin, $description, $imageAvant ?: null, $imageApres ?: null, $ordre]);
    logSecurityEvent('periode_creee', $admin['id'], ['titre' => $titre]);
    jsonResponse(['message' => 'Période ajoutée.'], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    $type = $body['type'] ?? 'periode';
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    $table = $type === 'figure' ? 'figures_historiques' : 'periode_evolution_benin';
    $pdo->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
    logSecurityEvent('frise_element_supprime', $admin['id'], ['id' => $id, 'type' => $type]);
    jsonResponse(['message' => 'Supprimé.']);
}

jsonError('Méthode non autorisée.', 405);
