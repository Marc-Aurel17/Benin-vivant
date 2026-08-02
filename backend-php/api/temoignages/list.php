<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT nom_auteur, contenu, created_at FROM temoignages WHERE is_published = 1 ORDER BY created_at DESC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkRateLimit('temoignage_soumis', 5, 300);
    $body = getJsonBody();
    $nomAuteur = cleanString($body['nom_auteur'] ?? '', 150);
    $contenu = cleanString($body['contenu'] ?? '', 2000);

    if ($nomAuteur === '' || $contenu === '') {
        jsonError('Nom et témoignage requis.', 422);
    }

    $user = currentUser();
    $stmt = $pdo->prepare(
        'INSERT INTO temoignages (user_id, nom_auteur, contenu, is_published, created_at) VALUES (?, ?, ?, 0, NOW())'
    );
    $stmt->execute([$user['id'] ?? null, $nomAuteur, $contenu]);

    jsonResponse(['message' => 'Merci pour votre témoignage, il sera publié après vérification.'], 201);
}

jsonError('Méthode non autorisée.', 405);
