<?php
/**
 * Configuration email (SMTP) + fonction d'envoi générique.
 *
 * Pourquoi PHPMailer et pas mail() natif de PHP ? Sous XAMPP, mail() dépend
 * de Mercury Mail (souvent mal configuré) ou d'un vrai serveur SMTP local —
 * dans les deux cas peu fiable. PHPMailer avec un vrai compte SMTP (Gmail,
 * Outlook, ou un service transactionnel comme Brevo/Mailjet) fonctionne à
 * l'identique en local et en production.
 *
 * Avec Gmail : active la validation en 2 étapes puis génère un
 * "mot de passe d'application" (myaccount.google.com/apppasswords) —
 * n'utilise JAMAIS ton mot de passe Gmail normal ici.
 */

require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'ton-adresse@gmail.com');       // à remplacer
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'xxxx xxxx xxxx xxxx');           // mot de passe d'application (16 caractères), pas ton vrai mot de passe
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Bénin Vivant');
define('SMTP_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);

/**
 * Envoie un email HTML. Retourne true/false, ne lève jamais d'exception
 * bloquante (un échec d'envoi ne doit jamais casser le flux principal —
 * ex: un webhook de paiement doit rester traité même si l'email échoue).
 */
function envoyerEmail(string $destinataire, string $sujet, string $corpsHtml, string $corpsTexte = ''): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($destinataire);

        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body = $corpsHtml;
        $mail->AltBody = $corpsTexte !== '' ? $corpsTexte : strip_tags($corpsHtml);

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('Échec envoi email vers ' . $destinataire . ' : ' . $mail->ErrorInfo);
        return false;
    }
}
