<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$pdo = getPDO();
$stmt = $pdo->query('SELECT id, question, reponse, categorie, ordre FROM faq ORDER BY categorie ASC, ordre ASC');

jsonResponse(['data' => $stmt->fetchAll()]);
