<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

requireRole('admin', 'super_admin');

$statutFiltre = $_GET['statut'] ?? 'en_attente';
$statutsAutorises = ['en_attente', 'valide', 'rejete', 'toutes'];
if (!in_array($statutFiltre, $statutsAutorises, true)) {
    jsonError('Statut invalide.', 422);
}

$pdo = getPDO();

$sql = 'SELECT c.id, c.type_contribution, c.titre, c.contenu, c.statut, c.created_at,
               u.prenom, u.nom, u.email
        FROM contributions_utilisateurs c
        JOIN users u ON u.id = c.user_id';
$params = [];

if ($statutFiltre !== 'toutes') {
    $sql .= ' WHERE c.statut = ?';
    $params[] = $statutFiltre;
}
$sql .= ' ORDER BY c.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

jsonResponse(['data' => $stmt->fetchAll()]);
