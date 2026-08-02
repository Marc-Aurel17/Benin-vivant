<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$themesAutorises = ['histoire', 'traditions', 'langues'];
$theme = $_GET['theme'] ?? 'histoire';
$nombre = isset($_GET['nombre']) ? min(20, max(1, (int) $_GET['nombre'])) : 10;

if (!in_array($theme, $themesAutorises, true)) {
    jsonError('Thème invalide.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'SELECT id, theme, question, reponse_a, reponse_b, reponse_c, reponse_d, niveau
     FROM quiz_questions WHERE theme = ? ORDER BY RAND() LIMIT ?'
);
// bindValue nécessaire pour LIMIT avec un entier (sinon PDO le traite comme une chaîne)
$stmt->bindValue(1, $theme, PDO::PARAM_STR);
$stmt->bindValue(2, $nombre, PDO::PARAM_INT);
$stmt->execute();

// La bonne réponse n'est JAMAIS renvoyée ici — uniquement lors de la validation
// côté serveur (api/quiz/valider.php), pour empêcher toute triche via la console navigateur.
jsonResponse(['data' => $stmt->fetchAll()]);
