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

// Seuls les projets validés (en_cours ou termine) sont visibles publiquement ;
// un projet "propose" attend encore la validation d'un modérateur.
$stmt = $pdo->query(
    "SELECT id, titre, slug, type_projet, description, porteur_projet,
            objectif_montant, montant_collecte, statut, created_at
     FROM projets_patrimoine
     WHERE statut IN ('en_cours', 'termine')
     ORDER BY FIELD(statut, 'en_cours', 'termine'), created_at DESC"
);
$projets = $stmt->fetchAll();

foreach ($projets as &$p) {
    $objectif = (float) $p['objectif_montant'];
    $collecte = (float) $p['montant_collecte'];
    $p['pourcentage_atteint'] = $objectif > 0 ? (int) round(min(100, ($collecte / $objectif) * 100)) : 0;
}
unset($p);

jsonResponse(['data' => $projets]);
