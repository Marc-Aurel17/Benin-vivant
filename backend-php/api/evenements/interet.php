<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('interet_evenement', 15, 60);

$body = getJsonBody();
$evenementId = isset($body['evenement_id']) ? (int) $body['evenement_id'] : 0;
$email = isset($body['email']) ? filter_var(trim($body['email']), FILTER_VALIDATE_EMAIL) : null;

if ($evenementId <= 0) {
    jsonError('Identifiant d\'événement invalide.', 422);
}

$user = currentUser();

if (!$user && !$email) {
    jsonError('Email requis si vous n\'êtes pas connecté (pour un éventuel rappel).', 422);
}

$pdo = getPDO();

// Vérifie que l'événement existe et est publié
$check = $pdo->prepare('SELECT id FROM evenements WHERE id = ? AND is_published = 1');
$check->execute([$evenementId]);
if (!$check->fetch()) {
    jsonError('Événement introuvable.', 404);
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO evenement_interesses (evenement_id, user_id, email, created_at) VALUES (?, ?, ?, NOW())'
    );
    $stmt->execute([$evenementId, $user['id'] ?? null, $user ? null : $email]);
} catch (PDOException $e) {
    // Contrainte UNIQUE violée = déjà inscrit, ce n'est pas une erreur pour l'utilisateur
    if ($e->getCode() === '23000') {
        jsonResponse(['message' => 'Vous êtes déjà inscrit à cet événement.']);
    }
    throw $e;
}

jsonResponse(['message' => 'Votre intérêt a été enregistré.'], 201);
