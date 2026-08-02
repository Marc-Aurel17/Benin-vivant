<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$reference = cleanString($_GET['ref'] ?? '', 100);
if ($reference === '') {
    jsonError('Référence manquante.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'SELECT d.statut, d.montant, d.donateur_nom, p.titre AS projet_titre
     FROM dons d JOIN projets_patrimoine p ON p.id = d.projet_id
     WHERE d.reference_transaction = ?'
);
$stmt->execute([$reference]);
$don = $stmt->fetch();

if (!$don) {
    jsonError('Don introuvable.', 404);
}

// IMPORTANT : cette page peut s'afficher AVANT que le webhook n'ait mis à jour
// le statut (redirection navigateur souvent plus rapide que l'appel serveur).
// Le frontend doit donc gérer un statut encore "en_attente" et proposer de
// rafraîchir, plutôt que d'annoncer un échec prématurément.
jsonResponse(['data' => $don]);
