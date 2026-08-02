<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$user = requireAuth();
requireCsrf();
checkRateLimit('creer_evenement', 10, 60);

$body = getJsonBody();

$typesAutorises = ['fete_traditionnelle', 'ceremonie_religieuse', 'festival_culturel', 'marche_special', 'commemoration', 'autre'];

$titre = cleanString($body['titre'] ?? '', 200);
$description = cleanString($body['description'] ?? '', 5000);
$type = $body['type_evenement'] ?? '';
$lieuNom = cleanString($body['lieu_nom'] ?? '', 200);
$ville = cleanString($body['ville'] ?? '', 100);
$departement = cleanString($body['departement'] ?? '', 100);
$dateDebut = $body['date_debut'] ?? '';
$dateFin = $body['date_fin'] ?? null;
$heureDebut = $body['heure_debut'] ?? null;
$heureFin = $body['heure_fin'] ?? null;
$estRecurrent = !empty($body['est_recurrent']) ? 1 : 0;
$frequence = $body['frequence_recurrence'] ?? 'ponctuel';
$lat = isset($body['latitude']) ? validateFloat($body['latitude'], -90, 90) : null;
$lng = isset($body['longitude']) ? validateFloat($body['longitude'], -180, 180) : null;
$groupeEthniqueId = isset($body['groupe_ethnique_id']) ? (int) $body['groupe_ethnique_id'] : null;
$siteHistoriqueId = isset($body['site_historique_id']) ? (int) $body['site_historique_id'] : null;

$errors = [];
if ($titre === '') $errors[] = 'Le titre est requis.';
if ($description === '') $errors[] = 'La description est requise.';
if (!in_array($type, $typesAutorises, true)) $errors[] = 'Type d\'événement invalide.';
if ($lieuNom === '') $errors[] = 'Le lieu est requis.';
if (!$dateDebut || !DateTime::createFromFormat('Y-m-d', $dateDebut)) $errors[] = 'Date de début invalide (format AAAA-MM-JJ).';
if ($dateFin && !DateTime::createFromFormat('Y-m-d', $dateFin)) $errors[] = 'Date de fin invalide.';
if ($dateFin && $dateDebut && $dateFin < $dateDebut) $errors[] = 'La date de fin ne peut pas précéder la date de début.';
if (!in_array($frequence, ['annuel', 'mensuel', 'ponctuel'], true)) $errors[] = 'Fréquence de récurrence invalide.';

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $titre), '-')) . '-' . uniqid();

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO evenements
     (slug, titre, description, type_evenement, groupe_ethnique_id, site_historique_id,
      lieu_nom, latitude, longitude, ville, departement,
      date_debut, date_fin, heure_debut, heure_fin, est_recurrent, frequence_recurrence,
      statut, is_published, created_by, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "a_venir", 0, ?, NOW(), NOW())'
);
$stmt->execute([
    $slug, $titre, $description, $type, $groupeEthniqueId ?: null, $siteHistoriqueId ?: null,
    $lieuNom, $lat, $lng, $ville, $departement,
    $dateDebut, $dateFin ?: null, $heureDebut ?: null, $heureFin ?: null, $estRecurrent, $frequence,
    $user['id'],
]);

$newId = (int) $pdo->lastInsertId();
logSecurityEvent('evenement_soumis', $user['id'], ['evenement_id' => $newId]);

jsonResponse(['message' => 'Événement soumis, en attente de validation par un modérateur.', 'id' => $newId], 201);
