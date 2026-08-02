<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM projets_patrimoine ORDER BY created_at DESC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $body = getJsonBody();
    $titre = cleanString($body['titre'] ?? '', 200);
    $typeProjet = $body['type_projet'] ?? '';
    $description = cleanString($body['description'] ?? '', 3000);
    $porteur = cleanString($body['porteur_projet'] ?? '', 200);
    $objectif = isset($body['objectif_montant']) ? (float) $body['objectif_montant'] : null;

    $typesAutorises = ['restauration', 'collecte_recits', 'numerisation_archives', 'initiative_scolaire'];
    if ($titre === '' || !in_array($typeProjet, $typesAutorises, true)) {
        jsonError('Titre et type de projet valide requis.', 422);
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $titre), '-')) . '-' . uniqid();

    $stmt = $pdo->prepare(
        'INSERT INTO projets_patrimoine (titre, slug, type_projet, description, porteur_projet, objectif_montant, statut, created_at)
         VALUES (?, ?, ?, ?, ?, ?, "propose", NOW())'
    );
    $stmt->execute([$titre, $slug, $typeProjet, $description, $porteur, $objectif]);
    logSecurityEvent('projet_cree', $admin['id'], ['titre' => $titre]);
    jsonResponse(['message' => 'Projet créé.'], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    $statut = $body['statut'] ?? '';
    $statutsAutorises = ['propose', 'en_cours', 'termine'];

    if ($id <= 0 || !in_array($statut, $statutsAutorises, true)) {
        jsonError('Paramètres invalides.', 422);
    }

    $pdo->prepare('UPDATE projets_patrimoine SET statut = ? WHERE id = ?')->execute([$statut, $id]);
    logSecurityEvent('projet_statut_modifie', $admin['id'], ['projet_id' => $id, 'statut' => $statut]);
    jsonResponse(['message' => 'Statut mis à jour.']);
}

jsonError('Méthode non autorisée.', 405);
