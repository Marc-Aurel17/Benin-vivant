<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

$slug = cleanString($_GET['slug'] ?? '', 220);
if ($slug === '') {
    jsonError('Paramètre slug requis.', 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM evenements WHERE slug = ? AND is_published = 1');
$stmt->execute([$slug]);
$e = $stmt->fetch();

if (!$e) {
    jsonError('Événement introuvable.', 404);
}

// Construit les horodatages au format iCalendar (UTC), avec ou sans heure précise
function formatIcsDate(string $date, ?string $heure): string
{
    $heure = $heure ?: '00:00:00';
    $dt = new DateTime("{$date} {$heure}", new DateTimeZone('Africa/Porto-Novo'));
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('Ymd\THis\Z');
}

$dtStart = formatIcsDate($e['date_debut'], $e['heure_debut']);
$dtEnd = formatIcsDate($e['date_fin'] ?? $e['date_debut'], $e['heure_fin'] ?? $e['heure_debut']);

// Échappement strict des champs texte dans le format ICS (virgules, points-virgules, retours ligne)
function escapeIcs(string $texte): string
{
    return str_replace(["\\", ",", ";", "\n"], ["\\\\", "\\,", "\\;", "\\n"], $texte);
}

$uid = 'evenement-' . $e['id'] . '@benin-vivant.bj';

$ics = "BEGIN:VCALENDAR\r\n"
     . "VERSION:2.0\r\n"
     . "PRODID:-//Benin Vivant//Evenements//FR\r\n"
     . "BEGIN:VEVENT\r\n"
     . "UID:{$uid}\r\n"
     . "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n"
     . "DTSTART:{$dtStart}\r\n"
     . "DTEND:{$dtEnd}\r\n"
     . "SUMMARY:" . escapeIcs($e['titre']) . "\r\n"
     . "DESCRIPTION:" . escapeIcs($e['description']) . "\r\n"
     . "LOCATION:" . escapeIcs($e['lieu_nom'] . ($e['ville'] ? ', ' . $e['ville'] : '')) . "\r\n"
     . "END:VEVENT\r\n"
     . "END:VCALENDAR\r\n";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $e['slug'] . '.ics"');
echo $ics;
