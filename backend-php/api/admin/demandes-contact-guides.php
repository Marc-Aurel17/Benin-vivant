<?php
/**
 * Vue d'ensemble admin : toutes les demandes de contact envoyées aux guides,
 * tous guides confondus. Le guide lui-même gère les siennes via
 * api/guides/mes-demandes.php — ceci est juste une supervision en lecture
 * pour l'admin (aucune modification ici).
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

requireRole('admin', 'super_admin');

$pdo = getPDO();
$stmt = $pdo->query(
    'SELECT d.id, d.visiteur_nom, d.visiteur_email, d.visiteur_telephone, d.message, d.statut, d.created_at,
            g.id AS guide_id, u.nom AS guide_nom, u.prenom AS guide_prenom
     FROM demandes_contact_guide d
     JOIN guides_touristiques g ON g.id = d.guide_id
     JOIN users u ON u.id = g.user_id
     ORDER BY d.created_at DESC'
);

jsonResponse(['data' => $stmt->fetchAll()]);
