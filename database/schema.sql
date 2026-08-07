-- =====================================================================
-- BÉNIN VIVANT : RACINES ET DIVERSITÉ
-- Schéma de base de données MySQL
-- Concours Digit'Héritage by Finanex
-- =====================================================================
-- Charset utf8mb4 obligatoire (accents, caractères Fon/Yoruba, emojis noms)
-- InnoDB partout : clés étrangères + transactions (paiements, dons)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 0. UTILISATEURS & RÔLES
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,               -- ID public non séquentiel (anti énumération)
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    telephone VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,          -- bcrypt/argon2, jamais en clair
    role ENUM('visiteur','contributeur','guide','admin','super_admin') NOT NULL DEFAULT 'contributeur',
    email_verified_at TIMESTAMP NULL,
    two_factor_secret TEXT NULL,                  -- 2FA admin (chiffré applicativement)
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    failed_login_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until TIMESTAMP NULL,                  -- anti brute-force
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    remember_token VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 1. MODULE 1 — ENCYCLOPÉDIE CULTURELLE
-- ---------------------------------------------------------------------
CREATE TABLE groupes_ethniques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    region_principale VARCHAR(150) NULL,
    histoire TEXT NULL,
    langue_principale VARCHAR(100) NULL,
    population_estimee VARCHAR(50) NULL,
    image_couverture VARCHAR(255) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,   -- validation modérateur avant publication
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ethnie_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE religions_traditions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    groupe_ethnique_id BIGINT UNSIGNED NOT NULL,
    titre VARCHAR(150) NOT NULL,
    fetiche_divinite VARCHAR(150) NULL,
    description TEXT NULL,
    CONSTRAINT fk_religion_ethnie FOREIGN KEY (groupe_ethnique_id) REFERENCES groupes_ethniques(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE plats_traditionnels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    groupe_ethnique_id BIGINT UNSIGNED NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT NULL,
    ingredients TEXT NULL,
    CONSTRAINT fk_plat_ethnie FOREIGN KEY (groupe_ethnique_id) REFERENCES groupes_ethniques(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE danses_traditionnelles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    groupe_ethnique_id BIGINT UNSIGNED NOT NULL,
    nom VARCHAR(150) NOT NULL,
    contexte_pratique TEXT NULL,
    description TEXT NULL,
    CONSTRAINT fk_danse_ethnie FOREIGN KEY (groupe_ethnique_id) REFERENCES groupes_ethniques(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tenues_traditionnelles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    groupe_ethnique_id BIGINT UNSIGNED NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT NULL,
    occasion_usage VARCHAR(200) NULL,
    CONSTRAINT fk_tenue_ethnie FOREIGN KEY (groupe_ethnique_id) REFERENCES groupes_ethniques(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE objets_art (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    groupe_ethnique_id BIGINT UNSIGNED NOT NULL,
    nom VARCHAR(150) NOT NULL,
    signification TEXT NULL,
    CONSTRAINT fk_objet_ethnie FOREIGN KEY (groupe_ethnique_id) REFERENCES groupes_ethniques(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table médias générique (galerie photos/vidéos), réutilisée par plusieurs modules
CREATE TABLE medias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mediable_type VARCHAR(100) NOT NULL,   -- ex: 'groupe_ethnique','site_historique','signalement'
    mediable_id BIGINT UNSIGNED NOT NULL,  -- polymorphisme façon Laravel (morphMany)
    type ENUM('image','video','audio') NOT NULL DEFAULT 'image',
    url VARCHAR(255) NOT NULL,
    legende VARCHAR(255) NULL,
    ordre SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mediable (mediable_type, mediable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2. MODULE 2 — HISTOIRE & ÉVOLUTION
-- ---------------------------------------------------------------------
CREATE TABLE figures_historiques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    periode VARCHAR(100) NULL,
    biographie TEXT NULL,
    portrait_url VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE periode_evolution_benin (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    categorie ENUM('royaume_precolonial','colonisation','esclavage','independance','moderne') NOT NULL,
    date_debut SMALLINT NULL,  -- pas YEAR : ce type MySQL est limité à 1901-2155, insuffisant pour les royaumes précoloniaux (dates dès 1500)
    date_fin SMALLINT NULL,
    description TEXT NULL,
    image_avant VARCHAR(255) NULL,   -- illustration "avant" (évolution moderne)
    image_apres VARCHAR(255) NULL,   -- illustration "après"
    ordre_frise SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_categorie (categorie),
    INDEX idx_ordre (ordre_frise)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. MODULE 3 — SITES HISTORIQUES GÉOLOCALISÉS
-- ---------------------------------------------------------------------
CREATE TABLE sites_historiques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    nom VARCHAR(200) NOT NULL,
    description TEXT NULL,
    histoire TEXT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    ville VARCHAR(100) NULL,
    departement VARCHAR(100) NULL,
    duree_visite_recommandee_min SMALLINT UNSIGNED NULL, -- marge de temps recommandée
    horaires_ouverture VARCHAR(150) NULL,
    tarif_entree VARCHAR(100) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_site_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_geo (latitude, longitude),
    INDEX idx_slug (slug),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4. MODULE 4 — GUIDES TOURISTIQUES
-- ---------------------------------------------------------------------
CREATE TABLE guides_touristiques (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    specialite VARCHAR(200) NULL,
    langues_parlees VARCHAR(255) NULL,     -- CSV ou JSON: "Fon,Français,Anglais"
    zone_couverte VARCHAR(200) NULL,
    telephone_pro VARCHAR(20) NULL,
    bio TEXT NULL,
    photo_profil VARCHAR(255) NULL,
    statut ENUM('en_attente','valide','suspendu') NOT NULL DEFAULT 'en_attente',
    note_moyenne DECIMAL(2,1) NULL,        -- V2 avis visiteurs
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_guide_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE demandes_contact_guide (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guide_id BIGINT UNSIGNED NOT NULL,
    visiteur_nom VARCHAR(150) NOT NULL,
    visiteur_email VARCHAR(190) NOT NULL,
    visiteur_telephone VARCHAR(20) NULL,
    message TEXT NOT NULL,
    statut ENUM('nouveau','lu','traite') NOT NULL DEFAULT 'nouveau',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_demande_guide FOREIGN KEY (guide_id) REFERENCES guides_touristiques(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. MODULE 5 — CARTE DES LANGUES
-- ---------------------------------------------------------------------
CREATE TABLE langues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    zone_geographique VARCHAR(200) NULL,
    latitude_centre DECIMAL(10,7) NULL,
    longitude_centre DECIMAL(10,7) NULL,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mots_langue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    langue_id BIGINT UNSIGNED NOT NULL,
    mot_expression VARCHAR(200) NOT NULL,
    traduction_fr VARCHAR(200) NOT NULL,
    audio_url VARCHAR(255) NULL,
    CONSTRAINT fk_mot_langue FOREIGN KEY (langue_id) REFERENCES langues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6. MODULE 6 — QUIZ & BADGES
-- ---------------------------------------------------------------------
CREATE TABLE quiz_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme ENUM('histoire','traditions','langues') NOT NULL,
    question TEXT NOT NULL,
    reponse_a VARCHAR(255) NOT NULL,
    reponse_b VARCHAR(255) NOT NULL,
    reponse_c VARCHAR(255) NULL,
    reponse_d VARCHAR(255) NULL,
    bonne_reponse ENUM('a','b','c','d') NOT NULL,
    explication TEXT NULL,
    niveau ENUM('facile','moyen','difficile') NOT NULL DEFAULT 'facile'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE badges_utilisateurs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    badge_code VARCHAR(60) NOT NULL,       -- ex: 'histoire_bronze', 'langues_or'
    score_total INT UNSIGNED NOT NULL DEFAULT 0,
    obtenu_le TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_badge_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_badge (user_id, badge_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 7. MODULE 8 — CONTRIBUTIONS COMMUNAUTAIRES
-- ---------------------------------------------------------------------
CREATE TABLE contributions_utilisateurs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type_contribution ENUM('tradition','site','information') NOT NULL,
    titre VARCHAR(200) NOT NULL,
    contenu TEXT NOT NULL,
    statut ENUM('en_attente','valide','rejete') NOT NULL DEFAULT 'en_attente',
    moderateur_id BIGINT UNSIGNED NULL,
    commentaire_moderation TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contrib_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_contrib_moderateur FOREIGN KEY (moderateur_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 8. MODULE 9 — SIGNALEMENTS PATRIMOINE
-- ---------------------------------------------------------------------
CREATE TABLE signalements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,          -- nullable: visiteur non connecté peut signaler
    type_probleme ENUM('site_degrade','monument_menace','tradition_en_danger','erreur_contenu') NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    statut ENUM('nouveau','en_cours','resolu','rejete') NOT NULL DEFAULT 'nouveau',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_signalement_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_statut_signalement (statut),
    INDEX idx_geo_signalement (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 9. MODULE 10 — PROJETS DE SAUVEGARDE
-- ---------------------------------------------------------------------
CREATE TABLE projets_patrimoine (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    type_projet ENUM('restauration','collecte_recits','numerisation_archives','initiative_scolaire') NOT NULL,
    description TEXT NOT NULL,
    porteur_projet VARCHAR(200) NULL,      -- école / association / commune
    objectif_montant DECIMAL(12,2) NULL,
    montant_collecte DECIMAL(12,2) NOT NULL DEFAULT 0,
    statut ENUM('propose','en_cours','termine') NOT NULL DEFAULT 'propose',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 10. MODULE 11 — DONS
-- ---------------------------------------------------------------------
CREATE TABLE dons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projet_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    donateur_nom VARCHAR(150) NULL,        -- si don anonyme non connecté
    donateur_email VARCHAR(190) NULL,      -- pour l'envoi du reçu de paiement
    montant DECIMAL(12,2) NOT NULL,
    devise VARCHAR(10) NOT NULL DEFAULT 'XOF',
    methode_paiement ENUM('mtn_momo','moov_money','fedapay') NOT NULL,
    reference_transaction VARCHAR(150) NOT NULL UNIQUE,  -- réf renvoyée par FedaPay/opérateur
    statut ENUM('en_attente','reussi','echoue','rembourse') NOT NULL DEFAULT 'en_attente',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_don_projet FOREIGN KEY (projet_id) REFERENCES projets_patrimoine(id) ON DELETE RESTRICT,
    CONSTRAINT fk_don_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_statut_don (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 11. MODULE 12 — ACTUALITÉS
-- ---------------------------------------------------------------------
CREATE TABLE actualites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    resume VARCHAR(500) NULL,
    contenu TEXT NOT NULL,
    source ENUM('interne','unesco','officiel') NOT NULL DEFAULT 'interne',
    image_couverture VARCHAR(255) NULL,
    auteur_id BIGINT UNSIGNED NULL,
    publie_le TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_actu_auteur FOREIGN KEY (auteur_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 12. SÉCURITÉ — journal d'audit (traçabilité anti-fraude / anti-intrusion)
-- ---------------------------------------------------------------------
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,          -- ex: 'login_failed','signalement_created','don_valide'
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    details JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 13. RATE LIMITING (utilisé par includes/security.php côté PHP)
-- ---------------------------------------------------------------------
CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(60) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action_time (ip_address, action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pense à vider périodiquement cette table (event scheduler MySQL ou cron) :
-- DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL 1 DAY;

-- ---------------------------------------------------------------------
-- 14. DEMANDES D'INSCRIPTION ADMINISTRATEUR (parcours en 3 étapes)
-- ---------------------------------------------------------------------
CREATE TABLE demandes_inscription_admin (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telephone VARCHAR(20) NULL,
    piece_identite_url VARCHAR(255) NULL,       -- upload étape "vérification d'identité"
    code_verification_email VARCHAR(10) NULL,   -- code à 6 chiffres, expire après usage
    email_verifie TINYINT(1) NOT NULL DEFAULT 0,
    token_activation VARCHAR(100) NULL UNIQUE,  -- lien d'activation envoyé après validation
    statut ENUM('etape_email','etape_identite','en_attente_validation','approuve','rejete','bloque')
        NOT NULL DEFAULT 'etape_email',
    commentaire_admin TEXT NULL,
    valide_par BIGINT UNSIGNED NULL,             -- super_admin qui a traité la demande
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_demande_admin_validateur FOREIGN KEY (valide_par) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_statut_demande (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 15. RÉGLAGES DU SITE (personnalisation super admin, clé/valeur)
-- ---------------------------------------------------------------------
CREATE TABLE site_settings (
    cle VARCHAR(100) PRIMARY KEY,
    valeur TEXT NULL,
    type ENUM('texte','couleur','nombre','image','booleen') NOT NULL DEFAULT 'texte',
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valeurs par défaut (identité + thème), modifiables ensuite depuis /admin/parametres
INSERT INTO site_settings (cle, valeur, type) VALUES
('site_nom', 'Bénin Vivant', 'texte'),
('site_slogan', 'Racines et Diversité', 'texte'),
('site_logo_url', '', 'image'),
('contact_email', 'contact@benin-vivant.bj', 'texte'),
('contact_telephone', '', 'texte'),
('reseau_whatsapp', '', 'texte'),
('reseau_facebook', '', 'texte'),
('reseau_instagram', '', 'texte'),
('reseau_tiktok', '', 'texte'),
('reseau_youtube', '', 'texte'),
('theme_defaut', 'sombre', 'texte'),
('couleur_principale', '#c99a2e', 'couleur'),
('couleur_accent', '#3f6653', 'couleur'),
('police_police', 'Inter', 'texte'),
('police_taille_base', '16', 'nombre'),
('police_taille_titres', '32', 'nombre'),
('police_interligne', '1.65', 'nombre'),
('texte_hero', 'Les racines du Bénin, vivantes et connectées', 'texte'),
('texte_mission', 'Faire vivre le patrimoine, pas seulement l''archiver', 'texte'),
('mentions_legales', '', 'texte');

-- ---------------------------------------------------------------------
-- 16. NEWSLETTER, CONTACT, TÉMOIGNAGES
-- ---------------------------------------------------------------------
CREATE TABLE newsletter_abonnes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    sujet VARCHAR(200) NULL,
    message TEXT NOT NULL,
    statut ENUM('nouveau','lu','traite') NOT NULL DEFAULT 'nouveau',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE temoignages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    nom_auteur VARCHAR(150) NOT NULL,
    contenu TEXT NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_temoignage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 17. MÉDIATHÈQUE, PARTENAIRES, FAQ, RAPPORTS
-- ---------------------------------------------------------------------
CREATE TABLE mediatheque (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    type ENUM('image','video') NOT NULL DEFAULT 'image',
    url VARCHAR(255) NOT NULL,
    categorie VARCHAR(100) NULL,   -- ex: 'sites', 'ethnies', 'evenements'
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE partenaires (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    logo_url VARCHAR(255) NULL,
    site_web VARCHAR(255) NULL,
    ordre SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE faq (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(300) NOT NULL,
    reponse TEXT NOT NULL,
    categorie VARCHAR(100) NULL,
    ordre SMALLINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rapports_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    fichier_url VARCHAR(255) NOT NULL,
    type ENUM('dossier_soumission','bilan','rapport') NOT NULL DEFAULT 'rapport',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 18. FORMATIONS (Module 6 étendu — contenu pédagogique)
-- ---------------------------------------------------------------------
CREATE TABLE formations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    contenu TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_formation_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ressources_formation (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formation_id BIGINT UNSIGNED NOT NULL,
    titre VARCHAR(200) NOT NULL,
    fichier_url VARCHAR(255) NOT NULL,
    CONSTRAINT fk_ressource_formation FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE commentaires_formation (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    contenu TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_commentaire_formation FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    CONSTRAINT fk_commentaire_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 19. PROPOSITIONS DE PROJETS (Module 10 — avant acceptation officielle)
-- ---------------------------------------------------------------------
CREATE TABLE propositions_projets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom_porteur VARCHAR(200) NOT NULL,      -- école / association / commune
    email_contact VARCHAR(190) NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    type_projet ENUM('restauration','collecte_recits','numerisation_archives','initiative_scolaire') NOT NULL,
    statut ENUM('nouveau','en_etude','accepte','rejete') NOT NULL DEFAULT 'nouveau',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_statut_proposition (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 20. ÉVÉNEMENTS TRADITIONNELS ET CULTURELS (Module 13)
-- ---------------------------------------------------------------------
CREATE TABLE evenements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(220) NOT NULL UNIQUE,
    titre VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    histoire_contexte TEXT NULL,                -- origine/signification de la tradition
    type_evenement ENUM('fete_traditionnelle','ceremonie_religieuse','festival_culturel','marche_special','commemoration','autre')
        NOT NULL DEFAULT 'fete_traditionnelle',

    -- Rattachements optionnels à l'existant (Module 1 et 3)
    groupe_ethnique_id BIGINT UNSIGNED NULL,
    site_historique_id BIGINT UNSIGNED NULL,

    -- Localisation (indépendante d'un site s'il n'y en a pas de rattaché)
    lieu_nom VARCHAR(200) NOT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    ville VARCHAR(100) NULL,
    departement VARCHAR(100) NULL,

    -- Dates et récurrence : beaucoup de fêtes béninoises reviennent chaque
    -- année à une date qui peut varier (calendrier lunaire/agricole, décision
    -- de la chefferie traditionnelle) — jamais une date fixe codée en dur.
    -- Un événement récurrent stocke son prochain passage dans date_debut,
    -- l'admin met à jour la date d'une année sur l'autre.
    date_debut DATE NOT NULL,
    date_fin DATE NULL,               -- NULL si événement d'un seul jour
    heure_debut TIME NULL,
    heure_fin TIME NULL,
    est_recurrent TINYINT(1) NOT NULL DEFAULT 0,
    frequence_recurrence ENUM('annuel','mensuel','ponctuel') NOT NULL DEFAULT 'ponctuel',

    -- Statut recalculé dynamiquement à l'affichage (voir api/evenements/list.php) ;
    -- stocké quand même pour permettre l'annulation manuelle, qui prime sur le calcul.
    statut ENUM('a_venir','en_cours','termine','annule') NOT NULL DEFAULT 'a_venir',

    entree_tarif VARCHAR(100) NULL,          -- ex: "Gratuit", "1 000 FCFA"
    organisateur VARCHAR(200) NULL,
    contact_email VARCHAR(190) NULL,
    contact_telephone VARCHAR(20) NULL,
    image_couverture VARCHAR(255) NULL,

    is_published TINYINT(1) NOT NULL DEFAULT 0,   -- modération obligatoire, comme le reste du contenu
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_evenement_ethnie FOREIGN KEY (groupe_ethnique_id) REFERENCES groupes_ethniques(id) ON DELETE SET NULL,
    CONSTRAINT fk_evenement_site FOREIGN KEY (site_historique_id) REFERENCES sites_historiques(id) ON DELETE SET NULL,
    CONSTRAINT fk_evenement_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_type_evenement (type_evenement),
    INDEX idx_statut_evenement (statut),
    INDEX idx_is_published_evenement (is_published),
    INDEX idx_geo_evenement (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- "Ça m'intéresse" — intention de participation, sans obliger à créer un compte.
-- Sert aussi de base pour un futur rappel email avant l'événement, et de jauge
-- d'affluence indicative pour les organisateurs.
CREATE TABLE evenement_interesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evenement_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NULL,          -- rempli si visiteur non connecté
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_interesse_evenement FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE,
    CONSTRAINT fk_interesse_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_evenement_user (evenement_id, user_id),
    UNIQUE KEY uniq_evenement_email (evenement_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Abonnement aux alertes d'événements, filtrable par région et/ou type
-- (distinct de newsletter_abonnes : ciblage plus précis, envois plus fréquents)
CREATE TABLE evenement_alertes_abonnes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    departement VARCHAR(100) NULL,               -- NULL = toutes régions
    type_evenement VARCHAR(50) NULL,              -- NULL = tous types
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_alerte (email, departement, type_evenement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exemples réels (à garder ou remplacer par du contenu vérifié) pour tester l'affichage
INSERT INTO evenements (slug, titre, type_evenement, description, lieu_nom, ville, departement, date_debut, est_recurrent, frequence_recurrence, statut, entree_tarif, is_published, created_at, updated_at) VALUES
('vodun-days-ouidah', 'Fête nationale du Vodun (Vodun Days)', 'ceremonie_religieuse', 'Célébration nationale annuelle des cultes vodun, rassemblant fidèles et visiteurs sur la plage de Ouidah avec processions, danses et offrandes.', 'Plage de Ouidah', 'Ouidah', 'Atlantique', '2027-01-10', 1, 'annuel', 'a_venir', 'Gratuit', 1, NOW(), NOW()),
('gaani-nikki', 'Fête du Gaani', 'fete_traditionnelle', 'Grande fête équestre et culturelle du peuple Bariba célébrant la fin du jeûne, avec parades de cavaliers, tambours et costumes royaux.', 'Place royale de Nikki', 'Nikki', 'Borgou', '2027-03-15', 1, 'annuel', 'a_venir', 'Gratuit', 1, NOW(), NOW()),
('egungun-porto-novo', 'Sortie des Egungun', 'ceremonie_religieuse', 'Cérémonie yoruba de sortie des masques Egungun, honorant les ancêtres à travers des danses rituelles dans les rues de la ville.', 'Centre-ville', 'Porto-Novo', 'Ouémé', '2026-11-02', 1, 'annuel', 'a_venir', 'Gratuit', 1, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
