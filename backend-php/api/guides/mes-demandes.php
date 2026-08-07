<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

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
