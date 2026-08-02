<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

// Validation stricte des coordonnées GPS entrantes (jamais de confiance
// aveugle dans les query params : bornes physiques réelles imposées).
$lat = isset($_GET['lat']) ? validateFloat($_GET['lat'], -90, 90) : null;
$lng = isset($_GET['lng']) ? validateFloat($_GET['lng'], -180, 180) : null;

if ((isset($_GET['lat']) && $lat === null) || (isset($_GET['lng']) && $lng === null)) {
    jsonError('Coordonnées GPS invalides.', 422);
}

$pdo = getPDO();
$stmt = $pdo->query(
    'SELECT id, slug, nom, description, latitude, longitude, ville, departement,
            duree_visite_recommandee_min, tarif_entree
     FROM sites_historiques
     WHERE is_published = 1'
);
$sites = $stmt->fetchAll();

if ($lat !== null && $lng !== null) {
    foreach ($sites as &$site) {
        $site['distance_km'] = calculerDistanceHaversine($lat, $lng, (float) $site['latitude'], (float) $site['longitude']);
    }
    unset($site);
    usort($sites, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
} else {
    usort($sites, fn($a, $b) => strcmp($a['nom'], $b['nom']));
}

jsonResponse(['data' => $sites]);

/**
 * Distance à vol d'oiseau en km (formule de Haversine).
 * Le tracé d'itinéraire précis (route réelle) est calculé côté client
 * par Leaflet Routing Machine ; cette fonction sert au tri/affichage rapide.
 */
function calculerDistanceHaversine(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $rayonTerreKm = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($rayonTerreKm * $c, 2);
}
