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
    'SELECT d.id, d.donateur_nom, d.donateur_email, d.montant, d.devise, d.methode_paiement,
            d.reference_transaction, d.statut, d.created_at, p.titre AS projet_titre
     FROM dons d JOIN projets_patrimoine p ON p.id = d.projet_id
     ORDER BY d.created_at DESC'
);
$dons = $stmt->fetchAll();

$total = $pdo->query("SELECT COALESCE(SUM(montant),0) s FROM dons WHERE statut = 'reussi'")->fetch()['s'];

jsonResponse(['data' => ['dons' => $dons, 'total_collecte' => (float) $total]]);
