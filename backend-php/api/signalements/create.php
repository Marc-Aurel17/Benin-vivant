<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// Accessible sans compte, donc throttle serré (anti-spam) : 10 requêtes/min/IP
checkRateLimit('signalement', 10, 60);

// Le formulaire envoie désormais du multipart/form-data (photo optionnelle) ;
// on retombe sur getJsonBody() si jamais un client envoie encore du JSON pur.
$body = $_POST ?: getJsonBody();

$typesAutorises = ['site_degrade', 'monument_menace', 'tradition_en_danger', 'erreur_contenu'];
$type = $body['type_probleme'] ?? '';
$titre = cleanString($body['titre'] ?? '', 200);
$description = cleanString($body['description'] ?? '', 3000);
$lat = isset($body['latitude']) && $body['latitude'] !== '' ? validateFloat($body['latitude'], -90, 90) : null;
$lng = isset($body['longitude']) && $body['longitude'] !== '' ? validateFloat($body['longitude'], -180, 180) : null;

$errors = [];
if (!in_array($type, $typesAutorises, true)) $errors[] = 'Type de problème invalide.';
if ($titre === '') $errors[] = 'Le titre est requis.';
if ($description === '') $errors[] = 'La description est requise.';

// --- Photo optionnelle : même validation stricte que pour les pièces d'identité ---
$photoUrl = null;
if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $fichier = $_FILES['photo'];
    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
    $tailleMaxOctets = 5 * 1024 * 1024; // 5 Mo

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors de l\'upload de la photo.';
    } elseif ($fichier['size'] > $tailleMaxOctets) {
        $errors[] = 'Photo trop volumineuse (5 Mo max).';
    } else {
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fichier['tmp_name']);
        finfo_close($finfo);
        $mimesAutorises = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!in_array($extension, $extensionsAutorisees, true) || !isset($mimesAutorises[$mime])) {
            $errors[] = 'Format de photo non autorisé (jpg, png, webp uniquement).';
        } else {
            $nomFichier = 'signalement_' . bin2hex(random_bytes(10)) . '.' . $mimesAutorises[$mime];
            $dossierUpload = __DIR__ . '/../../uploads/signalements/';
            if (!is_dir($dossierUpload)) {
                mkdir($dossierUpload, 0750, true);
            }
            move_uploaded_file($fichier['tmp_name'], $dossierUpload . $nomFichier);
            $photoUrl = '/uploads/signalements/' . $nomFichier;
        }
    }
}

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$user = currentUser(); // peut être null (visiteur non connecté)

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO signalements (user_id, type_probleme, titre, description, latitude, longitude, photo_url, statut, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, \'nouveau\', NOW(), NOW())'
);
$stmt->execute([$user['id'] ?? null, $type, $titre, $description, $lat, $lng, $photoUrl]);

logSecurityEvent('signalement_cree', $user['id'] ?? null, ['type' => $type]);

jsonResponse(['message' => 'Signalement enregistré, merci pour votre contribution à la préservation du patrimoine.'], 201);
