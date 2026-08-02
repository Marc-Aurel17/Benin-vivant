<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$admin = requireRole('admin', 'super_admin');
checkRateLimit('upload_media', 20, 60);

$titre = cleanString($_POST['titre'] ?? '', 200);
$categorie = cleanString($_POST['categorie'] ?? 'sites', 100);
$categoriesAutorisees = ['sites', 'ethnies', 'evenements'];

if ($titre === '' || !in_array($categorie, $categoriesAutorisees, true)) {
    jsonError('Titre et catégorie valide requis.', 422);
}

if (empty($_FILES['fichier'])) {
    jsonError('Fichier requis.', 422);
}

$fichier = $_FILES['fichier'];
$extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp', 'mp4'];
$tailleMaxOctets = 10 * 1024 * 1024; // 10 Mo

if ($fichier['error'] !== UPLOAD_ERR_OK) {
    jsonError('Erreur lors de l\'upload du fichier.', 422);
}
if ($fichier['size'] > $tailleMaxOctets) {
    jsonError('Fichier trop volumineux (10 Mo max).', 422);
}

$extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $extensionsAutorisees, true)) {
    jsonError('Format non autorisé (jpg, png, webp, mp4 uniquement).', 422);
}

// Vérifie le vrai type MIME, pas seulement l'extension (qui peut être falsifiée)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $fichier['tmp_name']);
finfo_close($finfo);
$mimesAutorises = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'];
if (!in_array($mime, $mimesAutorises, true)) {
    jsonError('Type de fichier invalide.', 422);
}

$type = str_starts_with($mime, 'video/') ? 'video' : 'image';

// Nom de fichier régénéré aléatoirement — jamais le nom original (qui peut
// contenir des caractères spéciaux, du path traversal, ou des infos indésirables)
$nomFichier = 'media_' . bin2hex(random_bytes(10)) . '.' . $extension;
$dossierUpload = __DIR__ . '/../../uploads/mediatheque/';
if (!is_dir($dossierUpload)) {
    mkdir($dossierUpload, 0755, true);
}
move_uploaded_file($fichier['tmp_name'], $dossierUpload . $nomFichier);

$urlPublique = rtrim(APP_URL, '/') . '/uploads/mediatheque/' . $nomFichier;

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO mediatheque (titre, type, url, categorie, created_at) VALUES (?, ?, ?, ?, NOW())'
);
$stmt->execute([$titre, $type, $urlPublique, $categorie]);

logSecurityEvent('media_uploade', $admin['id'], ['titre' => $titre, 'categorie' => $categorie]);

jsonResponse(['message' => 'Média ajouté avec succès.', 'url' => $urlPublique], 201);
