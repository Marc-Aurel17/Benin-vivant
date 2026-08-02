<?php
/**
 * Génère le HTML du reçu de don, envoyé par email après confirmation du
 * paiement (déclenché depuis le webhook FedaPay, statut "reussi").
 */

function genererRecuDonHtml(array $don, array $projet): string
{
    $montant = number_format((float) $don['montant'], 0, ',', ' ');
    $date = (new DateTime())->format('d/m/Y à H:i');
    $nom = htmlspecialchars($don['donateur_nom'] ?: 'Donateur', ENT_QUOTES, 'UTF-8');
    $projetTitre = htmlspecialchars($projet['titre'], ENT_QUOTES, 'UTF-8');
    $reference = htmlspecialchars($don['reference_transaction'], ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0; padding:0; background:#0e1119; font-family:Arial, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0e1119; padding:32px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#171d2c; border-radius:6px; overflow:hidden; border:1px solid #2a3348;">

        <tr><td style="background:#0e1119; padding:24px 32px; border-bottom:2px solid #c99a2e;">
          <span style="color:#ece2cc; font-size:20px; font-weight:bold;">Bénin Vivant</span>
          <span style="color:#c99a2e; font-size:20px;"> · Reçu de don</span>
        </td></tr>

        <tr><td style="padding:32px;">
          <p style="color:#ece2cc; font-size:16px; margin:0 0 16px;">Bonjour {$nom},</p>
          <p style="color:#a7a08e; font-size:14px; line-height:1.6; margin:0 0 24px;">
            Merci pour votre don en soutien au projet <strong style="color:#ece2cc;">{$projetTitre}</strong>.
            Votre paiement a été confirmé avec succès. Voici votre reçu :
          </p>

          <table width="100%" cellpadding="0" cellspacing="0" style="background:#1e2740; border-radius:4px; margin-bottom:24px;">
            <tr><td style="padding:12px 20px; border-bottom:1px solid #2a3348; color:#a7a08e; font-size:13px;">Montant</td>
                <td style="padding:12px 20px; border-bottom:1px solid #2a3348; color:#e3bc5c; font-size:13px; text-align:right; font-weight:bold;">{$montant} FCFA</td></tr>
            <tr><td style="padding:12px 20px; border-bottom:1px solid #2a3348; color:#a7a08e; font-size:13px;">Projet soutenu</td>
                <td style="padding:12px 20px; border-bottom:1px solid #2a3348; color:#ece2cc; font-size:13px; text-align:right;">{$projetTitre}</td></tr>
            <tr><td style="padding:12px 20px; border-bottom:1px solid #2a3348; color:#a7a08e; font-size:13px;">Référence</td>
                <td style="padding:12px 20px; border-bottom:1px solid #2a3348; color:#ece2cc; font-size:12px; text-align:right; font-family:monospace;">{$reference}</td></tr>
            <tr><td style="padding:12px 20px; color:#a7a08e; font-size:13px;">Date</td>
                <td style="padding:12px 20px; color:#ece2cc; font-size:13px; text-align:right;">{$date}</td></tr>
          </table>

          <p style="color:#a7a08e; font-size:13px; line-height:1.6; margin:0;">
            Ce reçu fait office de justificatif de paiement. Aucune donnée bancaire n'est
            conservée sur nos serveurs — le paiement a été traité directement par FedaPay.
          </p>
        </td></tr>

        <tr><td style="background:#0e1119; padding:20px 32px; text-align:center;">
          <span style="color:#595347; font-size:12px;">Bénin Vivant : Racines et Diversité — Concours Digit'Héritage by Finanex</span>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
