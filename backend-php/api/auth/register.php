<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// Anti spam d'inscriptions massives
checkRateLimit('register', 5, 60);

$body = getJsonBody();

$nom = cleanString($body['nom'] ?? '', 100);
$prenom = cleanString($body['prenom'] ?? '', 100);
$email = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = (string) ($body['password'] ?? '');
$telephone = cleanString($body['telephone'] ?? '', 20);

// --- Validation stricte ---------------------------------------------
$errors = [];
if ($nom === '') $errors[] = 'Le nom est requis.';
if ($prenom === '') $errors[] = 'Le prénom est requis.';
if (!$email) $errors[] = 'Adresse email invalide.';
if (strlen($password) < 10) $errors[] = 'Le mot de passe doit contenir au moins 10 caractères.';
if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $errors[] = 'Le mot de passe doit contenir au moins une majuscule et un chiffre.';
}

if ($errors) {
    jsonError(implode(' ', $errors), 422);
}

$pdo = getPDO();

// Vérifie l'unicité de l'email
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonError('Un compte existe déjà avec cet email.', 409);
}

$uuid = sprintf('%s-%s-%s-%s-%s',
    bin2hex(random_bytes(4)), bin2hex(random_bytes(2)),
    bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6))
);

// password_hash utilise bcrypt par défaut : jamais de mot de passe en clair stocké
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO users (uuid, nom, prenom, email, telephone, password_hash, role, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, "contributeur", NOW(), NOW())'
);
$stmt->execute([$uuid, $nom, $prenom, $email, $telephone, $passwordHash]);

$userId = (int) $pdo->lastInsertId();
logSecurityEvent('inscription', $userId, ['email' => $email]);

jsonResponse(['message' => 'Compte créé avec succès. Vous pouvez vous connecter.'], 201);
