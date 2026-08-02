<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$categorie = isset($_GET['categorie']) ? cleanString($_GET['categorie'], 100) : null;

$pdo = getPDO();

if ($categorie) {
    $stmt = $pdo->prepare('SELECT id, titre, type, url, categorie, created_at FROM mediatheque WHERE categorie = ? ORDER BY created_at DESC');
    $stmt->execute([$categorie]);
} else {
    $stmt = $pdo->query('SELECT id, titre, type, url, categorie, created_at FROM mediatheque ORDER BY created_at DESC');
}

jsonResponse(['data' => $stmt->fetchAll()]);
