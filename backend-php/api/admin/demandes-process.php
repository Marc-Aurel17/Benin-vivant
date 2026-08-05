<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonError('Méthode non autorisée.', 405);
}

$superAdmin = requireRole('super_admin');
requireCsrf();

$body = getJsonBody();
$demandeId = isset($body['id']) ? (int) $body['id'] : 0;
$action = $body['action'] ?? ''; // 'approuver' | 'rejeter' | 'bloquer'
$commentaire = cleanString($body['commentaire'] ?? '', 500);

if ($demandeId <= 0 || !in_array($action, ['approuver', 'rejeter', 'bloquer', 'debloquer'], true)) {
    jsonError('Paramètres invalides.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM demandes_inscription_admin WHERE id = ?');
$stmt->execute([$demandeId]);
$demande = $stmt->fetch();

if (!$demande) {
    jsonError('Demande introuvable.', 404);
}

$pdo->beginTransaction();
try {
    if ($action === 'approuver') {
        if ($demande['statut'] !== 'en_attente_validation') {
            throw new RuntimeException('Cette demande n\'est pas en attente de validation.');
        }

        // Crée le compte admin avec un mot de passe temporaire aléatoire fort ;
        // l'activation se fera via un lien à usage unique (token_activation),
        // jamais en envoyant le mot de passe en clair par email.
        $uuid = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
        $motDePasseTemporaire = bin2hex(random_bytes(16));
        $hash = password_hash($motDePasseTemporaire, PASSWORD_BCRYPT);

        $insertUser = $pdo->prepare(
            'INSERT INTO users (uuid, nom, prenom, email, telephone, password_hash, role, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, \'admin\', 1, NOW(), NOW())'
        );
        $insertUser->execute([$uuid, $demande['nom'], $demande['prenom'], $demande['email'], $demande['telephone'], $hash]);

        $token = bin2hex(random_bytes(32));
        $update = $pdo->prepare(
            'UPDATE demandes_inscription_admin
             SET statut = \'approuve\', token_activation = ?, valide_par = ?, commentaire_admin = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $update->execute([$token, $superAdmin['id'], $commentaire, $demandeId]);

        // TODO production : envoyer le lien d'activation (APP_URL/activer-admin?token=$token)
        // par email — jamais le mot de passe temporaire en clair.
        error_log("[DEMO] Lien d'activation admin : " . APP_URL . "/activer-admin.php?token={$token}");

    } elseif ($action === 'rejeter') {
        $update = $pdo->prepare(
            'UPDATE demandes_inscription_admin SET statut = \'rejete\', valide_par = ?, commentaire_admin = ?, updated_at = NOW() WHERE id = ?'
        );
        $update->execute([$superAdmin['id'], $commentaire, $demandeId]);
    } elseif ($action === 'bloquer') {
        $update = $pdo->prepare(
            'UPDATE demandes_inscription_admin SET statut = \'bloque\', valide_par = ?, commentaire_admin = ?, updated_at = NOW() WHERE id = ?'
        );
        $update->execute([$superAdmin['id'], $commentaire, $demandeId]);
    } else { // debloquer : remet la demande en attente de validation
        $update = $pdo->prepare(
            'UPDATE demandes_inscription_admin SET statut = \'en_attente_validation\', valide_par = ?, commentaire_admin = ?, updated_at = NOW() WHERE id = ?'
        );
        $update->execute([$superAdmin['id'], $commentaire, $demandeId]);
    }

    $pdo->commit();
} catch (RuntimeException $e) {
    $pdo->rollBack();
    jsonError($e->getMessage(), 409);
}

logSecurityEvent('demande_admin_traitee', $superAdmin['id'], ['demande_id' => $demandeId, 'action' => $action]);

jsonResponse(['message' => 'Demande traitée avec succès.']);
