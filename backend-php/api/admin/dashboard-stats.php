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

// Les 11 compteurs sont regroupés en une seule requête (sous-requêtes
// scalaires) au lieu de 11 allers-retours séquentiels : chaque connexion
// au dashboard ne coûte plus qu'une seule requête réseau vers MySQL.
// Chaque sous-requête s'appuie sur un index existant (statut/is_published/
// actif/role), donc reste un lookup indexé et non un balayage de table.
$sql = "SELECT
    (SELECT COUNT(*) FROM contributions_utilisateurs WHERE statut = 'en_attente') AS contributions_en_attente,
    (SELECT COUNT(*) FROM sites_historiques WHERE is_published = 0) AS sites_en_attente,
    (SELECT COUNT(*) FROM groupes_ethniques WHERE is_published = 0) AS ethnies_en_attente,
    (SELECT COUNT(*) FROM signalements WHERE statut IN ('nouveau','en_cours')) AS signalements_ouverts,
    (SELECT COUNT(*) FROM guides_touristiques WHERE statut = 'en_attente') AS guides_en_attente,
    (SELECT COUNT(*) FROM evenements WHERE is_published = 0) AS evenements_en_attente,
    (SELECT COALESCE(SUM(montant),0) FROM dons WHERE statut = 'reussi' AND MONTH(created_at) = MONTH(NOW())) AS dons_ce_mois,
    (SELECT COUNT(*) FROM demandes_inscription_admin WHERE statut = 'en_attente_validation') AS demandes_admin_attente,
    (SELECT COUNT(*) FROM contacts WHERE statut = 'nouveau') AS contacts_non_lus,
    (SELECT COUNT(*) FROM newsletter_abonnes WHERE actif = 1) AS abonnes_newsletter,
    (SELECT COUNT(*) FROM users WHERE role = 'contributeur') AS contributeurs_total";

$row = $pdo->query($sql)->fetch();

jsonResponse(['data' => [
    'contributions_en_attente' => (int) $row['contributions_en_attente'],
    'sites_en_attente'         => (int) $row['sites_en_attente'],
    'ethnies_en_attente'       => (int) $row['ethnies_en_attente'],
    'signalements_ouverts'     => (int) $row['signalements_ouverts'],
    'guides_en_attente'        => (int) $row['guides_en_attente'],
    'evenements_en_attente'    => (int) $row['evenements_en_attente'],
    'dons_ce_mois'             => (float) $row['dons_ce_mois'],
    'demandes_admin_attente'   => (int) $row['demandes_admin_attente'],
    'contacts_non_lus'         => (int) $row['contacts_non_lus'],
    'abonnes_newsletter'       => (int) $row['abonnes_newsletter'],
    'contributeurs_total'      => (int) $row['contributeurs_total'],
]]);
