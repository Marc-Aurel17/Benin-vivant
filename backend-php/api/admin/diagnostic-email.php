<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée.', 405);
}

$admin = requireRole('admin', 'super_admin');
requireCsrf();
checkRateLimit('diagnostic_email', 5, 60);

$body = getJsonBody();
$destinataire = filter_var(trim($body['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$destinataire) {
    jsonError('Adresse email de test invalide.', 422);
}

$envoye = envoyerEmail(
    $destinataire,
    '[Test] Bénin Vivant — Diagnostic SMTP',
    '<div style="font-family:Arial,sans-serif; padding:24px; background:#171d2c; color:#ece2cc;">'
    . '<h2 style="color:#c99a2e;">Test SMTP réussi ✓</h2>'
    . '<p>Si tu reçois cet email, la configuration SMTP de Bénin Vivant fonctionne correctement.</p>'
    . '<p style="color:#a7a08e; font-size:13px;">Envoyé le ' . (new DateTime())->format('d/m/Y à H:i') . ' par ' . htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) . '</p>'
    . '</div>'
);

logSecurityEvent('diagnostic_email_teste', $admin['id'], ['destinataire' => $destinataire, 'succes' => $envoye]);

if ($envoye) {
    jsonResponse(['message' => "Email de test envoyé avec succès à {$destinataire}. Vérifie sa boîte de réception (et les spams)."]);
} else {
    jsonError('Échec de l\'envoi. Vérifie SMTP_USER/SMTP_PASS dans config/mail.php et consulte les logs PHP pour le détail de l\'erreur.', 502);
}
