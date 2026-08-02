<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonError('Méthode non autorisée.', 405);
}

// Réservé exclusivement au rôle super_admin — même un admin classique n'y a pas accès.
$superAdmin = requireRole('super_admin');
requireCsrf();

$body = getJsonBody();

// Liste blanche stricte des clés modifiables (jamais de clé arbitraire acceptée,
// on ne fait jamais confiance à un tableau de clés fourni par le client).
$clesAutorisees = [
    'site_nom' => 'texte', 'site_slogan' => 'texte', 'site_logo_url' => 'image',
    'contact_email' => 'texte', 'contact_telephone' => 'texte',
    'reseau_whatsapp' => 'texte', 'reseau_facebook' => 'texte', 'reseau_instagram' => 'texte',
    'reseau_tiktok' => 'texte', 'reseau_youtube' => 'texte',
    'theme_defaut' => 'texte', 'couleur_principale' => 'couleur', 'couleur_accent' => 'couleur',
    'police_police' => 'texte', 'police_taille_base' => 'nombre',
    'police_taille_titres' => 'nombre', 'police_interligne' => 'nombre',
    'texte_hero' => 'texte', 'texte_mission' => 'texte',
    'mentions_legales' => 'texte',
];

$pdo = getPDO();
$maj = 0;

foreach ($clesAutorisees as $cle => $type) {
    if (!array_key_exists($cle, $body)) {
        continue;
    }

    $valeur = (string) $body[$cle];

    // Validation stricte selon le type déclaré (anti-injection CSS/HTML dans le thème)
    if ($type === 'couleur' && !preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $valeur)) {
        jsonError("Couleur invalide pour {$cle} (format hexadécimal attendu).", 422);
    }
    if ($type === 'nombre' && !preg_match('/^\d{1,4}(\.\d{1,3})?$/', $valeur)) {
        jsonError("Valeur numérique invalide pour {$cle}.", 422);
    }
    if ($type === 'texte') {
        $valeur = cleanString($valeur, 2000);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (cle, valeur, type, updated_at) VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE valeur = VALUES(valeur), updated_at = NOW()'
    );
    $stmt->execute([$cle, $valeur, $type]);
    $maj++;
}

logSecurityEvent('parametres_site_modifies', $superAdmin['id'], ['champs_modifies' => array_keys($body)]);

jsonResponse(['message' => "Réglages mis à jour ({$maj} champ(s)).", 'updated' => $maj]);
