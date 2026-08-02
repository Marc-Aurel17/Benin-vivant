<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

// Le quiz reste jouable sans compte, mais seul un compte connecté peut
// obtenir des points et débloquer des badges (comme annoncé côté frontend).
$user = currentUser();
if (!$user) {
    jsonResponse(['message' => 'Score non sauvegardé (connectez-vous pour gagner des badges).', 'badge_obtenu' => null]);
}

requireCsrf();
checkRateLimit('quiz_terminer', 20, 60);

$body = getJsonBody();
$theme = $body['theme'] ?? '';
$bonnesReponses = isset($body['bonnes_reponses']) ? (int) $body['bonnes_reponses'] : 0;
$totalQuestions = isset($body['total_questions']) ? (int) $body['total_questions'] : 0;

$themesAutorises = ['histoire', 'traditions', 'langues'];
if (!in_array($theme, $themesAutorises, true) || $totalQuestions <= 0 || $bonnesReponses < 0 || $bonnesReponses > $totalQuestions) {
    jsonError('Paramètres invalides.', 422);
}

// Seuils de badge par thème : bronze ≥ 50%, argent ≥ 75%, or = 100%
$pourcentage = ($bonnesReponses / $totalQuestions) * 100;
$niveauBadge = null;
if ($pourcentage >= 100) $niveauBadge = 'or';
elseif ($pourcentage >= 75) $niveauBadge = 'argent';
elseif ($pourcentage >= 50) $niveauBadge = 'bronze';

$pdo = getPDO();
$badgeObtenu = null;

if ($niveauBadge) {
    $badgeCode = $theme . '_' . $niveauBadge;

    // INSERT ... ON DUPLICATE KEY : ne remplace le badge que s'il est meilleur
    // qu'un badge déjà possédé pour ce même thème+niveau (score_total cumulatif)
    $stmt = $pdo->prepare(
        'INSERT INTO badges_utilisateurs (user_id, badge_code, score_total, obtenu_le)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE score_total = score_total + VALUES(score_total)'
    );
    $stmt->execute([$user['id'], $badgeCode, $bonnesReponses]);
    $badgeObtenu = $badgeCode;

    logSecurityEvent('badge_obtenu', $user['id'], ['badge' => $badgeCode, 'score' => "{$bonnesReponses}/{$totalQuestions}"]);
}

jsonResponse([
    'message' => $badgeObtenu ? "Bravo, badge {$niveauBadge} débloqué !" : 'Score enregistré, continuez pour débloquer un badge (50% minimum).',
    'badge_obtenu' => $badgeObtenu,
    'pourcentage' => round($pourcentage),
]);
