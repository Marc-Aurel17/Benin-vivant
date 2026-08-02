<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/theme.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

// Lecture publique : toute page du site peut récupérer l'identité/thème.
// Aucune donnée sensible n'est stockée dans site_settings (uniquement du
// contenu d'affichage), donc pas de restriction de rôle ici.
jsonResponse(['data' => getSiteSettings()]);
