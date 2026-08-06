<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonError('Méthode non autorisée.', 405);
}

$admin = requireRole('admin', 'super_admin');
requireCsrf();

$body = getJsonBody();
$id = isset($body['id']) ? (int) $body['id'] : 0;
if ($id <= 0) jsonError('Identifiant invalide.', 422);

$typesAutorises = ['fete_traditionnelle', 'ceremonie_religieuse', 'festival_culturel', 'marche_special', 'commemoration', 'autre'];

$titre = cleanString($body['titre'] ?? '', 200);
$description = cleanString($body['description'] ?? '', 5000);
$type = $body['type_evenement'] ?? '';
$lieuNom = cleanString($body['lieu_nom'] ?? '', 200);
$ville = cleanString($body['ville'] ?? '', 100);
$departement = cleanString($body['departement'] ?? '', 100);
$dateDebut = $body['date_debut'] ?? '';
$dateFin = $body['date_fin'] ?? null;
$estRecurrent = !empty($body['est_recurrent']) ? 1 : 0;
$frequence = $body['frequence_recurrence'] ?? 'ponctuel';
$entreeTarif = cleanString($body['entree_tarif'] ?? '', 100);
$organisateur = cleanString($body['organisateur'] ?? '', 200);
$imageCouverture = cleanString($body['image_couverture'] ?? '', 255);

$errors = [];
if ($titre === '') $errors[] = 'Le titre est requis.';
if (!in_array($type, $typesAutorises, true)) $errors[] = "Type d'événement invalide.";
if ($lieuNom === '') $errors[] = 'Le lieu est requis.';
if (!$dateDebut || !DateTime::createFromFormat('Y-m-d', $dateDebut)) $errors[] = 'Date de début invalide.';
if ($errors) jsonError(implode(' ', $errors), 422);

$pdo = getPDO();
if ($imageCouverture !== '') {
    $pdo->prepare(
        'UPDATE evenements SET titre = ?, description = ?, type_evenement = ?, lieu_nom = ?, ville = ?, departement = ?,
         date_debut = ?, date_fin = ?, est_recurrent = ?, frequence_recurrence = ?, entree_tarif = ?, organisateur = ?,
         image_couverture = ?, updated_at = NOW() WHERE id = ?'
    )->execute([$titre, $description, $type, $lieuNom, $ville, $departement, $dateDebut, $dateFin ?: null, $estRecurrent, $frequence, $entreeTarif ?: null, $organisateur ?: null, $imageCouverture, $id]);
} else {
    $pdo->prepare(
        'UPDATE evenements SET titre = ?, description = ?, type_evenement = ?, lieu_nom = ?, ville = ?, departement = ?,
         date_debut = ?, date_fin = ?, est_recurrent = ?, frequence_recurrence = ?, entree_tarif = ?, organisateur = ?,
         updated_at = NOW() WHERE id = ?'
    )->execute([$titre, $description, $type, $lieuNom, $ville, $departement, $dateDebut, $dateFin ?: null, $estRecurrent, $frequence, $entreeTarif ?: null, $organisateur ?: null, $id]);
}

logSecurityEvent('evenement_modifie', $admin['id'], ['evenement_id' => $id]);
jsonResponse(['message' => 'Événement modifié.']);
