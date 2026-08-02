<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

applySecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée.', 405);
}

requireRole('admin', 'super_admin');

$terme = trim($_GET['q'] ?? '');
if (mb_strlen($terme) < 2) {
    jsonResponse(['data' => []]); // évite de scanner toutes les tables pour 1 caractère
}

$pdo = getPDO();
$like = '%' . $terme . '%';
$resultats = [];

// Chaque recherche est volontairement limitée (LIMIT 5) et catégorisée avec
// un lien direct vers la page admin correspondante — la recherche globale
// sert à *retrouver* rapidement un contenu, pas à le lister exhaustivement.

$sites = $pdo->prepare('SELECT id, nom AS titre FROM sites_historiques WHERE nom LIKE ? LIMIT 5');
$sites->execute([$like]);
foreach ($sites->fetchAll() as $r) {
    $resultats[] = ['categorie' => 'Site historique', 'titre' => $r['titre'], 'lien' => 'admin-contenu.html'];
}

$ethnies = $pdo->prepare('SELECT id, nom AS titre FROM groupes_ethniques WHERE nom LIKE ? LIMIT 5');
$ethnies->execute([$like]);
foreach ($ethnies->fetchAll() as $r) {
    $resultats[] = ['categorie' => 'Groupe ethnique', 'titre' => $r['titre'], 'lien' => 'admin-contenu.html'];
}

$evenements = $pdo->prepare('SELECT id, titre FROM evenements WHERE titre LIKE ? LIMIT 5');
$evenements->execute([$like]);
foreach ($evenements->fetchAll() as $r) {
    $resultats[] = ['categorie' => 'Événement', 'titre' => $r['titre'], 'lien' => 'admin-evenements.html'];
}

$actualites = $pdo->prepare('SELECT id, titre FROM actualites WHERE titre LIKE ? LIMIT 5');
$actualites->execute([$like]);
foreach ($actualites->fetchAll() as $r) {
    $resultats[] = ['categorie' => 'Actualité', 'titre' => $r['titre'], 'lien' => 'admin-actualites.html'];
}

$projets = $pdo->prepare('SELECT id, titre FROM projets_patrimoine WHERE titre LIKE ? LIMIT 5');
$projets->execute([$like]);
foreach ($projets->fetchAll() as $r) {
    $resultats[] = ['categorie' => 'Projet', 'titre' => $r['titre'], 'lien' => 'admin-projets.html'];
}

$contributions = $pdo->prepare('SELECT id, titre FROM contributions_utilisateurs WHERE titre LIKE ? LIMIT 5');
$contributions->execute([$like]);
foreach ($contributions->fetchAll() as $r) {
    $resultats[] = ['categorie' => 'Contribution', 'titre' => $r['titre'], 'lien' => 'admin-contributions.html'];
}

$signalements = $pdo->prepare('SELECT id, titre FROM signalements WHERE titre LIKE ? LIMIT 5');
$signalements->execute([$like]);
foreach ($signalements->fetchAll() as $r) {
    $resultats[] = ['categorie' => 'Signalement', 'titre' => $r['titre'], 'lien' => 'admin-signalements.html'];
}

// Utilisateurs (nom, prénom OU email) — tous rôles confondus, avec le rôle affiché
$users = $pdo->prepare(
    "SELECT id, CONCAT(prenom, ' ', nom) AS titre, role
     FROM users WHERE prenom LIKE ? OR nom LIKE ? OR email LIKE ? LIMIT 5"
);
$users->execute([$like, $like, $like]);
foreach ($users->fetchAll() as $r) {
    $pageParRole = [
        'guide' => 'admin-guides.html',
        'contributeur' => 'admin-contributeurs.html',
        'admin' => 'admin-comptes.html',
        'super_admin' => 'admin-comptes.html',
    ];
    $resultats[] = [
        'categorie' => 'Compte (' . $r['role'] . ')',
        'titre' => $r['titre'],
        'lien' => $pageParRole[$r['role']] ?? 'admin-contributeurs.html',
    ];
}

$partenaires = $pdo->prepare('SELECT id, nom AS titre FROM partenaires WHERE nom LIKE ? LIMIT 5');
$partenaires->execute([$like]);
foreach ($partenaires->fetchAll() as $r) {
    $resultats[] = ['categorie' => 'Partenaire', 'titre' => $r['titre'], 'lien' => 'admin-partenaires.html'];
}

jsonResponse(['data' => array_slice($resultats, 0, 30)]);
