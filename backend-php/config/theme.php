<?php
/**
 * Charge les réglages du site (table site_settings) et expose une fonction
 * pour générer les variables CSS :root correspondantes.
 * Résultat mis en cache mémoire (statique) le temps de la requête.
 */

require_once __DIR__ . '/database.php';

function getSiteSettings(): array
{
    static $settings = null;

    if ($settings === null) {
        $pdo = getPDO();
        $stmt = $pdo->query('SELECT cle, valeur, type FROM site_settings');
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['cle']] = $row['valeur'];
        }
    }

    return $settings;
}

function getSetting(string $cle, string $defaut = ''): string
{
    $settings = getSiteSettings();
    return $settings[$cle] ?? $defaut;
}

/**
 * Génère le <style> à injecter dans le <head> de chaque page publique,
 * à partir des réglages définis par le super admin (/admin/parametres).
 * La couleur est validée par un pattern strict (anti-injection CSS/HTML).
 */
function renderThemeStyleTag(): string
{
    $couleurPrincipale = sanitizeCssColor(getSetting('couleur_principale', '#c99a2e'));
    $couleurAccent      = sanitizeCssColor(getSetting('couleur_accent', '#3f6653'));
    $police             = sanitizeFontName(getSetting('police_police', 'Inter'));
    $tailleBase         = sanitizeCssNumber(getSetting('police_taille_base', '16'));
    $tailleTitres       = sanitizeCssNumber(getSetting('police_taille_titres', '32'));
    $interligne         = sanitizeCssNumber(getSetting('police_interligne', '1.65'));

    return "<style>\n:root{\n"
        . "  --or: {$couleurPrincipale};\n"
        . "  --vert-patine: {$couleurAccent};\n"
        . "  --taille-base: {$tailleBase}px;\n"
        . "  --taille-titres: {$tailleTitres}px;\n"
        . "  --interligne: {$interligne};\n"
        . "}\n"
        . "body{ font-family:'{$police}', sans-serif; font-size:var(--taille-base); line-height:var(--interligne); }\n"
        . "</style>\n";
}

/** Accepte uniquement #rgb ou #rrggbb — rejette tout le reste (anti-injection CSS). */
function sanitizeCssColor(string $value): string
{
    return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value) ? $value : '#c99a2e';
}

/** N'autorise que lettres, chiffres, espaces et tirets dans un nom de police. */
function sanitizeFontName(string $value): string
{
    $clean = preg_replace('/[^A-Za-z0-9\s\-]/', '', $value);
    return $clean !== '' ? $clean : 'Inter';
}

/** Nombre décimal simple uniquement (tailles, interligne). */
function sanitizeCssNumber(string $value): string
{
    return preg_match('/^\d{1,4}(\.\d{1,3})?$/', $value) ? $value : '16';
}
