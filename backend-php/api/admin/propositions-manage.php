<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$admin = requireRole('admin', 'super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM propositions_projets ORDER BY FIELD(statut, "nouveau", "en_etude", "accepte", "rejete"), created_at DESC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    requireCsrf();
    $body = getJsonBody();
    $id = isset($body['id']) ? (int) $body['id'] : 0;
    $statut = $body['statut'] ?? '';
    $statutsAutorises = ['nouveau', 'en_etude', 'accepte', 'rejete'];

    if ($id <= 0 || !in_array($statut, $statutsAutorises, true)) {
        jsonError('Paramètres invalides.', 422);
    }

    $pdo = getPDO();

    // Si acceptée : crée automatiquement le projet officiel correspondant
    if ($statut === 'accepte') {
        $prop = $pdo->prepare('SELECT * FROM propositions_projets WHERE id = ?');
        $prop->execute([$id]);
        $p = $prop->fetch();
        if ($p) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $p['titre']), '-')) . '-' . uniqid();
            $pdo->prepare(
                'INSERT INTO projets_patrimoine (titre, slug, type_projet, description, porteur_projet, statut, created_at)
                 VALUES (?, ?, ?, ?, ?, "en_cours", NOW())'
            )->execute([$p['titre'], $slug, $p['type_projet'], $p['description'], $p['nom_porteur']]);
        }
    }

    $stmt = $pdo->prepare('UPDATE propositions_projets SET statut = ? WHERE id = ?');
    $stmt->execute([$statut, $id]);

    if ($stmt->rowCount() === 0 && $statut !== 'accepte') {
        jsonError('Proposition introuvable.', 404);
    }

    logSecurityEvent('proposition_traitee', $admin['id'], ['proposition_id' => $id, 'statut' => $statut]);
    jsonResponse(['message' => 'Statut mis à jour.' . ($statut === 'accepte' ? ' Projet officiel créé.' : '')]);
}

jsonError('Méthode non autorisée.', 405);
