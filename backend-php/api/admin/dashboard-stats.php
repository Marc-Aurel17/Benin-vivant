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

function compter(PDO $pdo, string $sql): int
{
    return (int) $pdo->query($sql)->fetch()['c'];
}

jsonResponse(['data' => [
    'contributions_en_attente' => compter($pdo, "SELECT COUNT(*) c FROM contributions_utilisateurs WHERE statut = 'en_attente'"),
    'sites_en_attente'         => compter($pdo, "SELECT COUNT(*) c FROM sites_historiques WHERE is_published = 0"),
    'ethnies_en_attente'       => compter($pdo, "SELECT COUNT(*) c FROM groupes_ethniques WHERE is_published = 0"),
    'signalements_ouverts'     => compter($pdo, "SELECT COUNT(*) c FROM signalements WHERE statut IN ('nouveau','en_cours')"),
    'guides_en_attente'        => compter($pdo, "SELECT COUNT(*) c FROM guides_touristiques WHERE statut = 'en_attente'"),
    'evenements_en_attente'    => compter($pdo, "SELECT COUNT(*) c FROM evenements WHERE is_published = 0"),
    'dons_ce_mois'             => (float) $pdo->query("SELECT COALESCE(SUM(montant),0) s FROM dons WHERE statut = 'reussi' AND MONTH(created_at) = MONTH(NOW())")->fetch()['s'],
    'demandes_admin_attente'   => compter($pdo, "SELECT COUNT(*) c FROM demandes_inscription_admin WHERE statut = 'en_attente_validation'"),
    'contacts_non_lus'         => compter($pdo, "SELECT COUNT(*) c FROM contacts WHERE statut = 'nouveau'"),
    'abonnes_newsletter'       => compter($pdo, "SELECT COUNT(*) c FROM newsletter_abonnes WHERE actif = 1"),
    'contributeurs_total'      => compter($pdo, "SELECT COUNT(*) c FROM users WHERE role = 'contributeur'"),
]]);
