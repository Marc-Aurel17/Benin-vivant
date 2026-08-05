<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = getPDO();
    $stmt = $pdo->query(
        'SELECT g.id, g.specialite, g.langues_parlees, g.zone_couverte, g.statut, g.created_at,
                u.nom, u.prenom, u.email
         FROM guides_touristiques g JOIN users u ON u.id = g.user_id
         ORDER BY FIELD(g.statut, \'en_attente\', \'valide\', \'suspendu\'), g.created_at DESC'
    );
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    $statut = $body['statut'] ?? '';
    $statutsAutorises = ['en_attente', 'valide', 'suspendu'];

    if ($id <= 0 || !in_array($statut, $statutsAutorises, true)) {
        jsonError('Paramètres invalides.', 422);
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE guides_touristiques SET statut = ? WHERE id = ?');
    $stmt->execute([$statut, $id]);

    if ($stmt->rowCount() === 0) {
        jsonError('Guide introuvable.', 404);
    }

    logSecurityEvent('guide_statut_modifie', $admin['id'], ['guide_id' => $id, 'nouveau_statut' => $statut]);
    jsonResponse(['message' => 'Statut mis à jour.']);
}

jsonError('Méthode non autorisée.', 405);
