<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if (!$id) {
    jsonError('Paramètre id invalide.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    "SELECT g.id, g.specialite, g.langues_parlees, g.zone_couverte, g.bio, g.photo_profil,
            g.note_moyenne, u.prenom, u.nom
     FROM guides_touristiques g
     JOIN users u ON u.id = g.user_id
     WHERE g.id = ? AND g.statut = 'valide'
     LIMIT 1"
);
$stmt->execute([$id]);
$guide = $stmt->fetch();

if (!$guide) {
    jsonError('Guide introuvable.', 404);
}

jsonResponse(['data' => $guide]);
