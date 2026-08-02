<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM quiz_questions ORDER BY theme, id DESC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $body = getJsonBody();
    $theme = $body['theme'] ?? '';
    $question = cleanString($body['question'] ?? '', 500);
    $reponseA = cleanString($body['reponse_a'] ?? '', 255);
    $reponseB = cleanString($body['reponse_b'] ?? '', 255);
    $reponseC = cleanString($body['reponse_c'] ?? '', 255);
    $reponseD = cleanString($body['reponse_d'] ?? '', 255);
    $bonneReponse = $body['bonne_reponse'] ?? '';
    $explication = cleanString($body['explication'] ?? '', 1000);
    $niveau = $body['niveau'] ?? 'facile';

    if (!in_array($theme, ['histoire', 'traditions', 'langues'], true)) jsonError('Thème invalide.', 422);
    if ($question === '' || $reponseA === '' || $reponseB === '') jsonError('Question et au moins 2 réponses requises.', 422);
    if (!in_array($bonneReponse, ['a', 'b', 'c', 'd'], true)) jsonError('Bonne réponse invalide.', 422);

    $stmt = $pdo->prepare(
        'INSERT INTO quiz_questions (theme, question, reponse_a, reponse_b, reponse_c, reponse_d, bonne_reponse, explication, niveau)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$theme, $question, $reponseA, $reponseB, $reponseC ?: null, $reponseD ?: null, $bonneReponse, $explication, $niveau]);
    logSecurityEvent('quiz_question_creee', $admin['id'], ['theme' => $theme]);
    jsonResponse(['message' => 'Question ajoutée.'], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    if ($id <= 0) jsonError('Identifiant invalide.', 422);

    $pdo->prepare('DELETE FROM quiz_questions WHERE id = ?')->execute([$id]);
    logSecurityEvent('quiz_question_supprimee', $admin['id'], ['question_id' => $id]);
    jsonResponse(['message' => 'Question supprimée.']);
}

jsonError('Méthode non autorisée.', 405);
