<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// Anti brute-force / credential stuffing : 5 tentatives par minute par IP
checkRateLimit('login', 5, 60);

$body = getJsonBody();
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = (string) ($body['password'] ?? '');

if (!$email || $password === '') {
    jsonError('Email et mot de passe requis.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'SELECT id, uuid, nom, prenom, email, password_hash, role, is_active,
            failed_login_attempts, locked_until
     FROM users WHERE email = ?'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

// Message volontairement générique (email inconnu ou mauvais mot de passe
// renvoient la même erreur) : évite l'énumération de comptes existants.
$genericError = 'Identifiants incorrects.';

if (!$user) {
    jsonError($genericError, 401);
}

if (isAccountLocked($user)) {
    logSecurityEvent('login_compte_verrouille', $user['id']);
    jsonError('Compte temporairement verrouillé suite à plusieurs échecs. Réessayez dans 15 minutes.', 423);
}

if (!$user['is_active']) {
    jsonError('Ce compte a été désactivé.', 403);
}

if (!password_verify($password, $user['password_hash'])) {
    registerFailedLogin((int) $user['id']);
    logSecurityEvent('login_echec', $user['id']);
    jsonError($genericError, 401);
}

// Connexion réussie : reset compteur, régénère l'ID de session (anti fixation de session)
resetFailedLogins((int) $user['id']);
session_regenerate_id(true);

$_SESSION['user'] = [
    'id' => (int) $user['id'],
    'uuid' => $user['uuid'],
    'nom' => $user['nom'],
    'prenom' => $user['prenom'],
    'email' => $user['email'],
    'role' => $user['role'],
];

$updateStmt = $pdo->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?');
$updateStmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);

logSecurityEvent('login_reussi', $user['id']);

jsonResponse([
    'message' => 'Connexion réussie.',
    'user' => $_SESSION['user'],
    'csrf_token' => generateCsrfToken(),
]);
