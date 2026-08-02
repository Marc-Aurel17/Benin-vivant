<?php
/**
 * Vérification de session, utilisée par le frontend au chargement de
 * mon-espace.html pour savoir si un visiteur est déjà connecté (ex : nouvel
 * onglet, où le jeton CSRF en mémoire JS a été perdu mais le cookie de
 * session PHP est toujours valide).
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$user = currentUser();

// Un jeton CSRF est renvoyé systématiquement (connecté ou non), pour que le
// frontend puisse s'en resservir immédiatement sans aller-retour supplémentaire.
jsonResponse([
    'user' => $user,
    'csrf_token' => generateCsrfToken(),
]);
