<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonError('Méthode non autorisée.', 405);
}

$user = requireAuth();
requireCsrf();
checkRateLimit('update_profile', 10, 60);

$body = getJsonBody();
$motDePasseActuel = (string) ($body['mot_de_passe_actuel'] ?? '');

if ($motDePasseActuel === '') {
    jsonError('Confirmation du mot de passe actuel requise pour toute modification.', 422);
}

$pdo = getPDO();

// Vérifie le mot de passe actuel AVANT toute modification — re-saisie
// obligatoire, comme annoncé côté frontend (protège contre un accès
// laissé ouvert sur un poste partagé).
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$hashActuel = $stmt->fetchColumn();

if (!password_verify($motDePasseActuel, $hashActuel)) {
    logSecurityEvent('profil_maj_mdp_incorrect', $user['id']);
    jsonError('Mot de passe actuel incorrect.', 401);
}

$champs = [];
$valeurs = [];

if (!empty($body['nom'])) { $champs[] = 'nom = ?'; $valeurs[] = cleanString($body['nom'], 100); }
if (!empty($body['prenom'])) { $champs[] = 'prenom = ?'; $valeurs[] = cleanString($body['prenom'], 100); }

if (!empty($body['email'])) {
    $email = filter_var(trim($body['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        jsonError('Email invalide.', 422);
    }
    $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $checkEmail->execute([$email, $user['id']]);
    if ($checkEmail->fetch()) {
        jsonError('Cet email est déjà utilisé par un autre compte.', 409);
    }
    $champs[] = 'email = ?';
    $valeurs[] = $email;
}

if (!empty($body['nouveau_mot_de_passe'])) {
    $nouveau = (string) $body['nouveau_mot_de_passe'];
    if (strlen($nouveau) < 10) {
        jsonError('Le nouveau mot de passe doit contenir au moins 10 caractères.', 422);
    }
    $champs[] = 'password_hash = ?';
    $valeurs[] = password_hash($nouveau, PASSWORD_BCRYPT);
}

if (empty($champs)) {
    jsonError('Aucune modification fournie.', 422);
}

$valeurs[] = $user['id'];
$sql = 'UPDATE users SET ' . implode(', ', $champs) . ', updated_at = NOW() WHERE id = ?';
$pdo->prepare($sql)->execute($valeurs);

logSecurityEvent('profil_modifie', $user['id'], ['champs' => array_keys($body)]);

jsonResponse(['message' => 'Profil mis à jour avec succès.']);
