-- Migration : ajout de l'upload photo pour les signalements (Module 9)
-- À exécuter une seule fois sur la base déjà en production.
ALTER TABLE signalements ADD COLUMN photo_url VARCHAR(255) NULL AFTER statut;
