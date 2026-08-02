<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$pdo = getPDO();

// Seuls les guides validés par un modérateur sont visibles publiquement.
$stmt = $pdo->query(
    "SELECT g.id, g.specialite, g.langues_parlees, g.zone_couverte, g.bio, g.photo_profil,
            g.note_moyenne, u.prenom, u.nom
     FROM guides_touristiques g
     JOIN users u ON u.id = g.user_id
     WHERE g.statut = 'valide'
     ORDER BY u.prenom"
);
$guides = $stmt->fetchAll();

jsonResponse(['data' => $guides]);
