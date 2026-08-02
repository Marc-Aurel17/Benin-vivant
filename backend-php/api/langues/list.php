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

$langues = $pdo->query('SELECT id, nom, zone_geographique, latitude_centre, longitude_centre, description FROM langues ORDER BY nom')->fetchAll();

// Un seul aller-retour BDD pour tous les mots (évite le N+1) puis regroupement en PHP
$mots = $pdo->query('SELECT langue_id, mot_expression, traduction_fr, audio_url FROM mots_langue ORDER BY id')->fetchAll();

$motsParLangue = [];
foreach ($mots as $mot) {
    $motsParLangue[$mot['langue_id']][] = $mot;
}

foreach ($langues as &$langue) {
    $langue['mots'] = $motsParLangue[$langue['id']] ?? [];
}
unset($langue);

jsonResponse(['data' => $langues]);
