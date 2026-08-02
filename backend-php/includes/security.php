<?php
/**
 * Sécurité : CSRF, authentification, contrôle de rôle (RBAC), anti brute-force,
 * rate limiting simple basé sur la BDD (table rate_limits, voir schema.sql).
 */

require_once __DIR__ . '/../config/database.php';

// ---------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    // hash_equals : comparaison en temps constant, anti timing-attack
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($token)) {
        jsonError('Jeton CSRF invalide ou manquant.', 419);
    }
}

// ---------------------------------------------------------------------
// Authentification / autorisation
// ---------------------------------------------------------------------
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireAuth(): array
{
    $user = currentUser();
    if (!$user) {
        jsonError('Authentification requise.', 401);
    }
    return $user;
}

function requireRole(string ...$roles): array
{
    $user = requireAuth();
    if (!in_array($user['role'], $roles, true)) {
        logSecurityEvent('acces_refuse', $user['id'], [
            'roles_requis' => $roles, 'role_actuel' => $user['role'],
        ]);
        jsonError('Accès refusé : permissions insuffisantes.', 403);
    }
    return $user;
}

// ---------------------------------------------------------------------
// Anti brute-force sur le login
// ---------------------------------------------------------------------
function isAccountLocked(array $user): bool
{
    if (empty($user['locked_until'])) {
        return false;
    }
    return strtotime($user['locked_until']) > time();
}

function registerFailedLogin(int $userId): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'UPDATE users SET failed_login_attempts = failed_login_attempts + 1,
         locked_until = IF(failed_login_attempts + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), locked_until)
         WHERE id = ?'
    );
    $stmt->execute([$userId]);
}

function resetFailedLogins(int $userId): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?');
    $stmt->execute([$userId]);
}

// ---------------------------------------------------------------------
// Rate limiting générique par IP + action (table rate_limits)
// ---------------------------------------------------------------------
function checkRateLimit(string $action, int $maxRequests, int $windowSeconds): void
{
    $pdo = getPDO();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS c FROM rate_limits
         WHERE ip_address = ? AND action = ? AND created_at > (NOW() - INTERVAL ? SECOND)'
    );
    $stmt->execute([$ip, $action, $windowSeconds]);
    $count = (int) $stmt->fetch()['c'];

    if ($count >= $maxRequests) {
        jsonError('Trop de requêtes. Réessayez dans quelques instants.', 429);
    }

    $insert = $pdo->prepare('INSERT INTO rate_limits (ip_address, action, created_at) VALUES (?, ?, NOW())');
    $insert->execute([$ip, $action]);
}

// ---------------------------------------------------------------------
// Journal de sécurité (table audit_logs)
// ---------------------------------------------------------------------
function logSecurityEvent(string $action, ?int $userId, array $details = []): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $userId,
        $action,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        json_encode($details, JSON_UNESCAPED_UNICODE),
    ]);
}
