-- Migration : ajout des index manquants sur les colonnes de filtre utilisées
-- par le tableau de bord admin (dashboard-stats.php) et les listes admin.
-- Sans ces index, chaque COUNT(*) WHERE ... fait un balayage complet de
-- table, ce qui ralentit la navigation dans tout le panneau admin.
-- À exécuter une seule fois sur une base déjà en production. Sans effet
-- sur une base fraîchement créée avec schema.sql (les index y sont déjà).

ALTER TABLE groupes_ethniques ADD INDEX idx_is_published (is_published);
ALTER TABLE sites_historiques ADD INDEX idx_is_published (is_published);
ALTER TABLE evenements ADD INDEX idx_is_published_evenement (is_published);
ALTER TABLE contacts ADD INDEX idx_statut (statut);
ALTER TABLE newsletter_abonnes ADD INDEX idx_actif (actif);
