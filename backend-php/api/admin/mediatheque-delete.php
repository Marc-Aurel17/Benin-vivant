<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonError('Méthode non autorisée.', 405);
}

$admin = requireRole('admin', 'super_admin');
requireCsrf();

$body = getJsonBody();
$id = isset($body['id']) ? (int) $body['id'] : 0;
if ($id <= 0) {
    jsonError('Identifiant invalide.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT url FROM mediatheque WHERE id = ?');
$stmt->execute([$id]);
$media = $stmt->fetch();

if (!$media) {
    jsonError('Média introuvable.', 404);
}

// Supprime le fichier physique s'il est hébergé localement (pas une URL externe)
$cheminAttendu = rtrim(APP_URL, '/') . '/uploads/mediatheque/';
if (str_starts_with($media['url'], $cheminAttendu)) {
    $nomFichier = basename($media['url']);
    $chemin = __DIR__ . '/../../uploads/mediatheque/' . $nomFichier;
    if (is_file($chemin)) {
        unlink($chemin);
    }
}

$pdo->prepare('DELETE FROM mediatheque WHERE id = ?')->execute([$id]);

logSecurityEvent('media_supprime', $admin['id'], ['media_id' => $id]);

jsonResponse(['message' => 'Média supprimé.']);
