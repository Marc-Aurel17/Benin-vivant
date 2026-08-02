<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$slug = cleanString($_GET['slug'] ?? '', 220);
if ($slug === '') {
    jsonError('Paramètre slug requis.', 422);
}

$pdo = getPDO();

$stmt = $pdo->prepare(
    "SELECT id, titre, slug, type_projet, description, porteur_projet,
            objectif_montant, montant_collecte, statut, created_at
     FROM projets_patrimoine
     WHERE slug = ? AND statut IN ('en_cours', 'termine')
     LIMIT 1"
);
$stmt->execute([$slug]);
$projet = $stmt->fetch();

if (!$projet) {
    jsonError('Projet introuvable.', 404);
}

$objectif = (float) $projet['objectif_montant'];
$collecte = (float) $projet['montant_collecte'];
$projet['pourcentage_atteint'] = $objectif > 0 ? (int) round(min(100, ($collecte / $objectif) * 100)) : 0;

// Transparence : dons réussis les plus récents, avec anonymisation (prénom +
// initiale du nom pour les utilisateurs connectés ; jamais l'email/téléphone).
$stmtDons = $pdo->prepare(
    "SELECT d.montant, d.created_at, d.donateur_nom, u.prenom, u.nom
     FROM dons d
     LEFT JOIN users u ON u.id = d.user_id
     WHERE d.projet_id = ? AND d.statut = 'reussi'
     ORDER BY d.created_at DESC
     LIMIT 10"
);
$stmtDons->execute([$projet['id']]);
$donsRecents = array_map(function ($d) {
    if ($d['prenom']) {
        $nomAffiche = $d['prenom'] . ' ' . mb_substr($d['nom'], 0, 1) . '.';
    } else {
        $nomAffiche = $d['donateur_nom'] ?: 'Anonyme';
    }
    return ['nom_affiche' => $nomAffiche, 'montant' => $d['montant'], 'date' => $d['created_at']];
}, $stmtDons->fetchAll());

$stmtCount = $pdo->prepare("SELECT COUNT(*) AS c FROM dons WHERE projet_id = ? AND statut = 'reussi'");
$stmtCount->execute([$projet['id']]);
$nombreDonateurs = (int) $stmtCount->fetch()['c'];

jsonResponse(['data' => [
    'projet' => $projet,
    'dons_recents' => $donsRecents,
    'nombre_donateurs' => $nombreDonateurs,
]]);
