<?php
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
    "SELECT e.id, e.titre, e.type_evenement, e.lieu_nom, e.date_debut, e.est_recurrent,
            e.statut, e.is_published, e.created_at,
            u.prenom AS createur_prenom, u.nom AS createur_nom,
            (SELECT COUNT(*) FROM evenement_interesses i WHERE i.evenement_id = e.id) AS nb_interesses
     FROM evenements e LEFT JOIN users u ON u.id = e.created_by
     ORDER BY e.is_published ASC, e.date_debut ASC"
);

jsonResponse(['data' => $stmt->fetchAll()]);
