<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $user = requireAuth();
    if ($user['role'] !== 'guide') jsonError('Réservé aux comptes guide.', 403);
    requireCsrf();

    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    $statut = $body['statut'] ?? '';
    if ($id <= 0 || !in_array($statut, ['lu', 'traite'], true)) jsonError('Paramètres invalides.', 422);

    $pdo = getPDO();
    // Vérifie que la demande appartient bien à ce guide avant de la modifier
    $verif = $pdo->prepare(
        'SELECT d.id FROM demandes_contact_guide d
         JOIN guides_touristiques g ON g.id = d.guide_id
         WHERE d.id = ? AND g.user_id = ?'
    );
    $verif->execute([$id, $user['id']]);
    if (!$verif->fetch()) jsonError('Demande introuvable.', 404);

    $pdo->prepare('UPDATE demandes_contact_guide SET statut = ? WHERE id = ?')->execute([$statut, $id]);
    jsonResponse(['message' => 'Statut mis à jour.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$user = requireAuth();

if ($user['role'] !== 'guide') {
    jsonError('Réservé aux comptes guide.', 403);
}

$pdo = getPDO();

// Retrouve la fiche guide liée à ce compte utilisateur
$guide = $pdo->prepare('SELECT id FROM guides_touristiques WHERE user_id = ?');
$guide->execute([$user['id']]);
$guideRow = $guide->fetch();

if (!$guideRow) {
    jsonResponse(['data' => []]); // pas encore de fiche guide créée
}

$stmt = $pdo->prepare(
    'SELECT id, visiteur_nom, visiteur_email, visiteur_telephone, message, statut, created_at
     FROM demandes_contact_guide WHERE guide_id = ? ORDER BY created_at DESC'
);
$stmt->execute([$guideRow['id']]);

jsonResponse(['data' => $stmt->fetchAll()]);
