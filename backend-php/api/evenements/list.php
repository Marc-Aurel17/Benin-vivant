<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$typesAutorises = ['fete_traditionnelle', 'ceremonie_religieuse', 'festival_culturel', 'marche_special', 'commemoration', 'autre'];

$type = $_GET['type'] ?? null;
$mois = $_GET['mois'] ?? null;       // format "2026-08"
$departement = isset($_GET['departement']) ? cleanString($_GET['departement'], 100) : null;

$conditions = ['is_published = 1'];
$params = [];

// Par défaut, on ne montre pas les événements déjà terminés (sauf demande explicite)
if (!isset($_GET['inclure_termines'])) {
    $conditions[] = "(date_fin IS NULL AND date_debut >= CURDATE()) OR (date_fin IS NOT NULL AND date_fin >= CURDATE())";
}

if ($type !== null) {
    if (!in_array($type, $typesAutorises, true)) {
        jsonError('Type d\'événement invalide.', 422);
    }
    $conditions[] = 'type_evenement = ?';
    $params[] = $type;
}

if ($mois !== null) {
    if (!preg_match('/^\d{4}-\d{2}$/', $mois)) {
        jsonError('Format de mois invalide (attendu AAAA-MM).', 422);
    }
    $conditions[] = 'DATE_FORMAT(date_debut, "%Y-%m") = ?';
    $params[] = $mois;
}

if ($departement !== null && $departement !== '') {
    $conditions[] = 'departement = ?';
    $params[] = $departement;
}

$pdo = getPDO();
$sql = 'SELECT id, slug, titre, type_evenement, lieu_nom, ville, departement,
               date_debut, date_fin, heure_debut, heure_fin, est_recurrent,
               statut, image_couverture, entree_tarif, latitude, longitude
        FROM evenements
        WHERE ' . implode(' AND ', $conditions) . '
        ORDER BY date_debut ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$evenements = $stmt->fetchAll();

// Statut calculé dynamiquement à l'affichage (jamais laissé stagner en base)
$aujourdhui = date('Y-m-d');
foreach ($evenements as &$e) {
    if ($e['statut'] === 'annule') {
        continue; // l'annulation manuelle prime toujours sur le calcul automatique
    }
    $fin = $e['date_fin'] ?? $e['date_debut'];
    if ($aujourdhui < $e['date_debut']) {
        $e['statut'] = 'a_venir';
    } elseif ($aujourdhui > $fin) {
        $e['statut'] = 'termine';
    } else {
        $e['statut'] = 'en_cours';
    }
}
unset($e);

jsonResponse(['data' => $evenements]);
