<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

checkRateLimit('quiz_valider', 60, 60); // généreux : un quiz enchaîne vite les questions

$body = getJsonBody();
$questionId = isset($body['question_id']) ? (int) $body['question_id'] : 0;
$reponseChoisie = $body['reponse'] ?? ''; // 'a' | 'b' | 'c' | 'd'

if ($questionId <= 0 || !in_array($reponseChoisie, ['a', 'b', 'c', 'd'], true)) {
    jsonError('Paramètres invalides.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT bonne_reponse, explication FROM quiz_questions WHERE id = ?');
$stmt->execute([$questionId]);
$question = $stmt->fetch();

if (!$question) {
    jsonError('Question introuvable.', 404);
}

$correct = $reponseChoisie === $question['bonne_reponse'];

jsonResponse([
    'correct' => $correct,
    'bonne_reponse' => $question['bonne_reponse'],
    'explication' => $question['explication'],
]);
