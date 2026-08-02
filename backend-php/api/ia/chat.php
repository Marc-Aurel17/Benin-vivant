<?php
/**
 * Module 7 — Guide culturel intelligent.
 * Simple relais sécurisé vers le microservice Python (ai-service/) : ce
 * fichier ne fait JAMAIS d'appel IA lui-même. Le microservice n'est jamais
 * exposé directement au navigateur (pas de CORS public, clé interne requise
 * via X-Api-Key), donc tout passe par ici.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// Accessible sans compte (visiteur), donc throttle par IP pour limiter les
// coûts d'appel au modèle et l'abus : 15 questions / minute.
checkRateLimit('ia_chat', 15, 60);

$body = getJsonBody();
$question = cleanString($body['question'] ?? '', 500);
$langue = in_array($body['langue'] ?? 'fr', ['fr', 'en'], true) ? $body['langue'] : 'fr';

if ($question === '') {
    jsonError('La question ne peut pas être vide.', 422);
}

$payload = json_encode(['question' => $question, 'langue' => $langue], JSON_UNESCAPED_UNICODE);

$ch = curl_init(rtrim(AI_SERVICE_URL, '/') . '/chat');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Api-Key: ' . AI_SERVICE_API_KEY,
    ],
    CURLOPT_TIMEOUT => 20,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false) {
    error_log('Appel microservice IA échoué : ' . $curlError);
    jsonError("Le guide culturel IA est momentanément indisponible. Vérifiez qu'ai-service (Python) est bien démarré sur " . AI_SERVICE_URL, 503);
}

$data = json_decode($responseBody, true);

if ($httpCode !== 200 || !is_array($data)) {
    error_log('Réponse invalide du microservice IA (HTTP ' . $httpCode . ') : ' . $responseBody);
    jsonError('Le guide culturel IA a renvoyé une réponse invalide.', 502);
}

logSecurityEvent('ia_question_posee', currentUser()['id'] ?? null, ['longueur_question' => strlen($question)]);

jsonResponse([
    'reponse' => $data['reponse'] ?? '',
    'sources' => $data['sources'] ?? [],
]);
