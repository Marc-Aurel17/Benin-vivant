<?php
/**
 * Connexion à la base de données via PDO.
 * PDO + requêtes préparées = protection native contre l'injection SQL,
 * à condition de TOUJOURS utiliser des paramètres liés (jamais de concaténation).
 */

// En XAMPP : la BDD locale s'appelle "benin_vivant" (voir database/schema.sql)
// En production (Render) : ces variables sont lues depuis les variables
// d'environnement définies dans le dashboard Render (Environment), et
// retombent sur les valeurs XAMPP locales si elles ne sont pas définies.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'benin_vivant');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');       // mets un mot de passe même en local si possible
define('DB_CHARSET', 'utf8mb4');

// Certificat CA requis par les hébergeurs MySQL managés qui imposent le SSL
// (ex: Aiven). Laisse vide en local/XAMPP. En production, DB_SSL_CA doit
// pointer vers le fichier ca.pem copié dans backend-php/config/ (voir
// docs/PASSAGE-EN-LIVE.md ou le guide de déploiement Render).
define('DB_SSL_CA', getenv('DB_SSL_CA') ?: '');

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // vraies requêtes préparées côté serveur MySQL
            // Sans ceci, une connexion qui ne répond pas (mauvais host/port,
            // pare-feu, hôte MySQL en veille) peut bloquer PHP plusieurs
            // dizaines de secondes voire plus avant d'échouer, ce qui gèle
            // chaque endpoint admin et fait planter l'onglet du navigateur
            // qui attend la réponse. 5s est largement suffisant pour une
            // connexion qui fonctionne normalement.
            PDO::ATTR_TIMEOUT             => 5,
        ];

        if (DB_SSL_CA !== '' && file_exists(DB_SSL_CA)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Ne JAMAIS renvoyer le message d'erreur PDO brut au client (fuite d'infos:
            // identifiants, structure de la BDD). On logge côté serveur seulement.
            error_log('Erreur connexion BDD : ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erreur serveur, réessayez plus tard.']);
            exit;
        }
    }

    return $pdo;
}
