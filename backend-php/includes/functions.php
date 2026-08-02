<?php
/**
 * Fonctions utilitaires partagées par tous les endpoints API.
 */

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $statusCode = 400): void
{
    jsonResponse(['error' => $message], $statusCode);
}

/**
 * En-têtes de sécurité HTTP (OWASP). À appeler en tête de chaque endpoint.
 */
function applySecurityHeaders(): void
{
    header('X-Frame-Options: DENY');                  // anti clickjacking
    header('X-Content-Type-Options: nosniff');         // anti MIME sniffing
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data: https:;");

    // CORS : autorise uniquement ton propre front. En local (XAMPP), tout
    // ce qui commence par http://localhost passe. En production (Render),
    // définis la variable d'environnement ALLOWED_ORIGINS avec l'URL exacte
    // de ton frontend (ex: https://benin-vivant-frontend.onrender.com),
    // plusieurs origines séparées par une virgule si besoin.
    $allowedOrigins = array_filter(array_map('trim', explode(',',
        getenv('ALLOWED_ORIGINS') ?: 'http://localhost'
    )));
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        foreach ($allowedOrigins as $allowedOrigin) {
            if (str_starts_with($_SERVER['HTTP_ORIGIN'], $allowedOrigin)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Credentials: true');
                break;
            }
        }
    }
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Nettoie une chaîne de caractères entrante (défense en profondeur en plus
 * des requêtes préparées et de l'échappement à l'affichage).
 */
function cleanString(?string $value, int $maxLength = 1000): string
{
    if ($value === null) {
        return '';
    }
    $value = trim($value);
    $value = substr($value, 0, $maxLength);
    // Neutralise les balises HTML/JS dans les champs texte libres (anti-XSS stocké)
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function validateFloat($value, float $min, float $max): ?float
{
    if (!is_numeric($value)) {
        return null;
    }
    $f = (float) $value;
    if ($f < $min || $f > $max) {
        return null;
    }
    return $f;
}

/**
 * Lit et décode le corps JSON de la requête en tableau associatif.
 */
function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
