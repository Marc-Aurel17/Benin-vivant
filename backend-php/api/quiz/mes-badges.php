<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$user = requireAuth();

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT badge_code, score_total, obtenu_le FROM badges_utilisateurs WHERE user_id = ? ORDER BY obtenu_le DESC');
$stmt->execute([$user['id']]);

jsonResponse(['data' => $stmt->fetchAll()]);
