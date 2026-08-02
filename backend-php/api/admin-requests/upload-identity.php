<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('demande_admin_identite', 5, 300);

$demandeId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;

if ($demandeId <= 0) {
    jsonError('Identifiant de demande requis.', 422);
}

if (empty($_FILES['piece_identite'])) {
    jsonError('Fichier de pièce d\'identité requis.', 422);
}

$fichier = $_FILES['piece_identite'];

// --- Validation stricte de l'upload (anti upload de fichier malveillant) ---
$extensionsAutorisees = ['jpg', 'jpeg', 'png', 'pdf'];
$tailleMaxOctets = 5 * 1024 * 1024; // 5 Mo

if ($fichier['error'] !== UPLOAD_ERR_OK) {
    jsonError('Erreur lors de l\'upload du fichier.', 422);
}
if ($fichier['size'] > $tailleMaxOctets) {
    jsonError('Fichier trop volumineux (5 Mo max).', 422);
}

$extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $extensionsAutorisees, true)) {
    jsonError('Format de fichier non autorisé (jpg, png, pdf uniquement).', 422);
}

// Vérifie le vrai type MIME (pas seulement l'extension, qui peut être falsifiée)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $fichier['tmp_name']);
finfo_close($finfo);
$mimesAutorises = ['image/jpeg', 'image/png', 'application/pdf'];
if (!in_array($mime, $mimesAutorises, true)) {
    jsonError('Type de fichier invalide.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT id FROM demandes_inscription_admin WHERE id = ? AND statut = "etape_identite"');
$stmt->execute([$demandeId]);
if (!$stmt->fetch()) {
    jsonError('Demande introuvable ou étape déjà passée.', 404);
}

// Nom de fichier régénéré (jamais le nom original, qui peut contenir du code/path traversal)
$nomFichier = 'identite_' . $demandeId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
$dossierUpload = __DIR__ . '/../../uploads/identites/';
if (!is_dir($dossierUpload)) {
    mkdir($dossierUpload, 0750, true);
}
move_uploaded_file($fichier['tmp_name'], $dossierUpload . $nomFichier);

$update = $pdo->prepare(
    'UPDATE demandes_inscription_admin
     SET piece_identite_url = ?, statut = "en_attente_validation", updated_at = NOW()
     WHERE id = ?'
);
$update->execute(['/uploads/identites/' . $nomFichier, $demandeId]);

logSecurityEvent('demande_admin_identite_soumise', null, ['demande_id' => $demandeId]);

jsonResponse(['message' => 'Pièce d\'identité reçue. Votre demande est en attente de validation par le super administrateur.']);
