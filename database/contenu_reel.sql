-- =====================================================================
-- BÉNIN VIVANT — CONTENU RÉEL DE LANCEMENT
-- =====================================================================
-- À importer APRÈS database/schema.sql.
-- Contenu rédigé et vérifié (histoire, patrimoine, géographie du Bénin) —
-- remplace/complète les données de démonstration utilisées dans les
-- maquettes frontend. Tout est publié (is_published = 1) directement,
-- car il s'agit ici de contenu de référence validé par l'équipe, pas de
-- contributions communautaires nécessitant modération.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 0. COMPTE SUPER ADMIN INITIAL (indispensable — sans lui, impossible de
--    se connecter au panneau admin ni d'approuver de futures demandes
--    d'inscription admin : c'est le premier compte, il n'y a personne
--    pour l'approuver lui-même).
-- ---------------------------------------------------------------------
-- Email : admin@benin-vivant.bj
-- Mot de passe : BeninVivant2026!
-- ⚠️ CHANGE CE MOT DE PASSE DÈS TA PREMIÈRE CONNEXION (page profil admin) —
-- il est en clair dans ce fichier, donc à usage de test local uniquement.
INSERT INTO users (uuid, nom, prenom, email, password_hash, role, is_active, email_verified_at, created_at, updated_at) VALUES
('81331449-f19c-a94c-ad03-f3abdfb9a67a', 'Admin', 'Super', 'admin@benin-vivant.bj', '$2y$10$Tr5ngofh86dxof33DSUhDubeGPF6ckEvNqDY2nyQPLkkRZKh54guq', 'super_admin', 1, NOW(), NOW(), NOW());

-- ---------------------------------------------------------------------
-- MODULE 10 — PROJETS DE SAUVEGARDE (nécessaires pour tester le Module 11 — Dons)
-- ---------------------------------------------------------------------
INSERT INTO projets_patrimoine (titre, slug, type_projet, description, porteur_projet, objectif_montant, montant_collecte, statut, created_at) VALUES
('Restauration des bas-reliefs du Palais royaux d''Abomey', 'restauration-bas-reliefs-abomey', 'restauration', 'Consolidation et restauration de 40 bas-reliefs historiés endommagés par l''érosion, selon les techniques traditionnelles de fabrication du banco, avec formation d''artisans locaux.', 'École du Patrimoine Africain', 5000000.00, 0.00, 'en_cours', NOW()),
('Mémoire orale des griots du Borgou', 'memoire-orale-griots-borgou', 'collecte_recits', 'Enregistrement audio et vidéo des récits des griots Bariba avant qu''ils ne se perdent, avec transcription et mise à disposition publique.', 'Association Gaani Mémoire', 3000000.00, 0.00, 'en_cours', NOW()),
('Numérisation des archives du lycée de Porto-Novo', 'numerisation-archives-porto-novo', 'numerisation_archives', 'Numérisation de documents historiques sur l''époque coloniale conservés par l''établissement depuis 1930.', 'Lycée Toffa 1er, Porto-Novo', 1500000.00, 0.00, 'propose', NOW());

-- ---------------------------------------------------------------------
-- MODULE 1 — GROUPES ETHNIQUES
-- ---------------------------------------------------------------------
INSERT INTO groupes_ethniques (slug, nom, region_principale, histoire, langue_principale, population_estimee, is_published, created_at, updated_at) VALUES
('fon', 'Fon', 'Zou, Atlantique, Littoral, Collines', 'Peuple fondateur du royaume de Danxomè (Dahomey), établi à Abomey au XVIIe siècle sous le règne du roi Houégbadja. Le royaume fon a dominé le sud du Bénin actuel pendant près de trois siècles, développant une organisation politique et militaire structurée, notamment le corps des Agojie (guerrières royales). Les Fon constituent aujourd''hui le groupe ethnique le plus nombreux du Bénin.', 'Fongbé', 'Environ 1,7 million de locuteurs natifs', 1, NOW(), NOW()),
('yoruba', 'Yoruba', 'Ouémé, Plateau', 'Présents des deux côtés de la frontière avec le Nigéria, les Yoruba du Bénin sont notamment établis autour de Porto-Novo et Kétou, ancienne cité royale liée aux dynasties yoruba d''Ilé-Ifè. Porto-Novo, capitale politique du Bénin, tire une partie de son identité culturelle de cet héritage yoruba mêlé à l''influence afro-brésilienne des descendants d''esclaves affranchis revenus d''Amérique au XIXe siècle.', 'Yoruba', 'Environ 1,2 million de locuteurs', 1, NOW(), NOW()),
('bariba', 'Bariba', 'Borgou, Alibori', 'Peuple du nord-est du Bénin, historiquement organisé autour de royaumes tels que Nikki, Kandi et Parakou. Société traditionnellement hiérarchisée entre nobles (Wasangari), cavaliers guerriers, et castes artisanales. Les Bariba sont réputés pour leur maîtrise équestre, célébrée chaque année lors de la fête du Gaani.', 'Baatonum', 'Environ 970 000 locuteurs', 1, NOW(), NOW()),
('somba', 'Somba (Betammaribè)', 'Atacora (Boukoumbé, Natitingou)', 'Peuple de l''Atacora connu pour son habitat troglodyte fortifié, les Tata Somba — maisons-forteresses en terre à étages construites pour se protéger des razzias esclavagistes et des invasions. Société sans royauté centralisée, organisée en communautés villageoises autonomes, avec une forte tradition initiatique (Dikuntri, rite de passage masculin).', 'Ditamari', 'Environ 250 000 locuteurs', 1, NOW(), NOW()),
('adja', 'Adja', 'Mono, Couffo', 'Peuple considéré comme la souche commune dont seraient issus les Fon et les Aja-Tado, originaire de la région de Tado (actuel Togo). Fondateurs historiques du royaume d''Allada au XVe-XVIe siècle, dont seraient issues les trois dynasties royales d''Allada, Porto-Novo et Abomey selon la tradition orale.', 'Adja', 'Environ 640 000 locuteurs', 1, NOW(), NOW()),
('dendi', 'Dendi', 'Alibori, Borgou (Malanville, Kandi)', 'Héritiers de commerçants et lettrés venus de l''empire Songhaï après sa chute face aux Marocains au XVIe siècle. Installés le long du fleuve Niger, les Dendi ont historiquement joué un rôle de relais commercial et d''islamisation dans le nord du Bénin.', 'Dendi', 'Environ 280 000 locuteurs', 1, NOW(), NOW()),
('fulani', 'Peul (Fulani)', 'Présents dans tout le nord du Bénin', 'Peuple traditionnellement pasteur nomade, présent au Bénin principalement dans les départements du Borgou, de l''Alibori et de l''Atacora. Organisés en clans autour de l''élevage transhumant du zébu, les Peuls du Bénin partagent une culture et une langue communes avec les communautés peules d''Afrique de l''Ouest.', 'Fulfuldé', 'Environ 800 000 locuteurs', 1, NOW(), NOW());

-- Détails religion/spiritualité, plats, danses, tenues pour le Fon (fiche la plus complète, à titre d'exemple pour le modèle)
INSERT INTO religions_traditions (groupe_ethnique_id, titre, fetiche_divinite, description)
SELECT id, 'Culte vodun', 'Legba, Mawu-Lisa, Sakpata', 'Le vodun (littéralement « esprit » ou « force invisible » en fongbé) est le socle spirituel traditionnel des Fon, reconnu religion à part entière au Bénin. Chaque divinité (vodun) est associée à une force naturelle ou sociale : Legba (gardien des carrefours), Sakpata (la terre et la variole), Hêviosso (le tonnerre). Le culte s''exprime par des cérémonies rythmées par le tambour et la transe.' FROM groupes_ethniques WHERE slug = 'fon';

INSERT INTO plats_traditionnels (groupe_ethnique_id, nom, description)
SELECT id, 'Amiwo', 'Pâte de maïs rouge relevée au piment et à la tomate, souvent accompagnée de viande ou de poisson fumé — plat emblématique des grandes occasions chez les Fon.' FROM groupes_ethniques WHERE slug = 'fon';

INSERT INTO danses_traditionnelles (groupe_ethnique_id, nom, contexte_pratique, description)
SELECT id, 'Sato', 'Funérailles et cérémonies vodun', 'Danse rituelle exécutée lors des cérémonies funéraires royales et des grandes cérémonies vodun, accompagnée de percussions et de chants en fongbé.' FROM groupes_ethniques WHERE slug = 'fon';

-- ---------------------------------------------------------------------
-- MODULE 3 — SITES HISTORIQUES GÉOLOCALISÉS
-- ---------------------------------------------------------------------
INSERT INTO sites_historiques (slug, nom, description, histoire, latitude, longitude, ville, departement, duree_visite_recommandee_min, horaires_ouverture, tarif_entree, is_published, created_at, updated_at) VALUES
('palais-royaux-abomey', 'Palais royaux d''Abomey', 'Ensemble de dix palais construits successivement par les rois du Danxomè entre le XVIIe et le XIXe siècle, inscrit au patrimoine mondial de l''UNESCO depuis 1985.', 'Fondée vers 1625 par le roi Houégbadja, Abomey fut la capitale du royaume du Danxomè jusqu''à la conquête coloniale française de 1894. Chaque roi y fit édifier son propre palais, orné de bas-reliefs en terre cuite narrant les hauts faits de son règne. Une partie du site fut détruite par un incendie en 1892 lors de la résistance du roi Béhanzin face aux troupes coloniales.', 7.1829000, 1.9905000, 'Abomey', 'Zou', 90, '9h00 – 17h30 (fermé certains jours fériés)', '2 000 FCFA (résidents), 5 000 FCFA (non-résidents)', 1, NOW(), NOW()),
('porte-non-retour-ouidah', 'Porte du Non-Retour', 'Mémorial érigé sur la plage de Ouidah, marquant le point d''embarquement des personnes réduites en esclavage vers les Amériques entre le XVIIe et le XIXe siècle.', 'Ouidah fut l''un des principaux ports négriers d''Afrique de l''Ouest. La Route des Esclaves relie le centre-ville à cette porte, jalonnée de monuments retraçant le parcours des captifs jusqu''à leur déportation. Le monument actuel, orné de bas-reliefs, a été érigé en 1995 dans le cadre du festival international Ouidah 92.', 6.3606000, 2.0850000, 'Ouidah', 'Atlantique', 45, 'Accès libre en journée', 'Gratuit', 1, NOW(), NOW()),
('musee-honme-porto-novo', 'Musée Honmè (Palais royal de Porto-Novo)', 'Ancien palais des rois de Porto-Novo, transformé en musée retraçant l''histoire de la royauté goun et l''influence afro-brésilienne de la ville.', 'Construit à la fin du XIXe siècle sous le règne du roi Toffa Ier, le palais Honmè mêle architecture traditionnelle et influences afro-brésiliennes, héritées des anciens esclaves affranchis revenus du Brésil au XIXe siècle qui ont façonné une partie de l''architecture de Porto-Novo.', 6.4969000, 2.6289000, 'Porto-Novo', 'Ouémé', 60, '9h00 – 17h00, fermé le lundi', '1 000 FCFA', 1, NOW(), NOW()),
('cite-royale-nikki', 'Cité royale de Nikki', 'Siège historique de la royauté bariba, où se tient chaque année la grande fête équestre du Gaani.', 'Nikki fut fondée au XVIIe siècle et demeure le centre spirituel et politique de la royauté bariba (Wasangari). La ville conserve un système de chefferie traditionnelle toujours actif, reconnu aux côtés de l''administration républicaine moderne.', 9.9377000, 3.2109000, 'Nikki', 'Borgou', 60, 'Visite sur rendez-vous auprès de la chefferie locale', 'Contribution libre', 1, NOW(), NOW()),
('tata-somba-boukoumbe', 'Tata Somba de Boukoumbé', 'Habitat troglodyte fortifié traditionnel du peuple Betammaribè (Somba), classé au patrimoine culturel national et proposé au patrimoine mondial de l''UNESCO.', 'Ces maisons-forteresses en terre battue à deux étages, dotées de tourelles défensives, furent conçues pour protéger les familles des razzias esclavagistes aux XVIIe-XIXe siècles. Chaque Tata abrite encore aujourd''hui une famille selon une organisation de l''espace liée aux rites initiatiques Somba.', 10.1833000, 1.1000000, 'Boukoumbé', 'Atacora', 75, 'Visite guidée recommandée, prévoir un guide local', '3 000 FCFA (avec guide)', 1, NOW(), NOW()),
('temple-pythons-ouidah', 'Temple des Pythons', 'Temple vodun dédié au python royal (Dangbé), situé face à la basilique de Ouidah — symbole de la coexistence religieuse au Bénin.', 'Le python est vénéré dans la tradition vodun comme symbole de sagesse et de fertilité. Le temple abrite une soixantaine de pythons royaux, considérés comme sacrés et inoffensifs, que les visiteurs peuvent approcher sous la conduite des prêtres du temple.', 6.3614000, 2.0847000, 'Ouidah', 'Atlantique', 30, '8h00 – 18h00', '1 000 FCFA', 1, NOW(), NOW()),
('foret-sacree-kpasse', 'Forêt sacrée de Kpassè', 'Forêt sacrée abritant le tombeau symbolique du roi Kpassè, fondateur légendaire de Ouidah, et une collection de statues représentant les divinités vodun.', 'Selon la tradition orale, le roi Kpassè se serait métamorphosé en iroko (arbre sacré) à cet emplacement pour échapper à ses ennemis. Le site conserve un arbre séculaire considéré comme la trace de cette légende fondatrice.', 6.3651000, 2.0862000, 'Ouidah', 'Atlantique', 40, '8h00 – 18h00', '1 000 FCFA', 1, NOW(), NOW());

-- ---------------------------------------------------------------------
-- MODULE 2 — FIGURES ET PÉRIODES HISTORIQUES
-- ---------------------------------------------------------------------
INSERT INTO figures_historiques (nom, periode, biographie, created_at) VALUES
('Houégbadja', 'v.1645 – v.1685', 'Troisième roi du Danxomè, considéré comme le véritable fondateur d''Abomey en tant que capitale royale. Il institua les fondements administratifs et militaires du royaume.', NOW()),
('Agaja', '1708 – 1740', 'Roi conquérant qui étendit le royaume du Danxomè jusqu''à la côte atlantique en soumettant le royaume d''Allada puis celui de Ouidah, donnant au Danxomè un accès direct au commerce maritime.', NOW()),
('Ghézo', '1818 – 1858', 'Roi réformateur qui renforça l''armée du Danxomè, notamment le corps des Agojie (guerrières), et chercha à diversifier l''économie du royaume face au déclin progressif de la traite négrière.', NOW()),
('Glèlè', '1858 – 1889', 'Fils de Ghézo, il poursuivit la consolidation du royaume et fit édifier son propre palais au sein du site royal d''Abomey.', NOW()),
('Béhanzin', '1889 – 1894', 'Dernier roi du Danxomè indépendant, il mena une résistance armée déterminée contre la conquête coloniale française lors des guerres franco-dahoméennes (1890-1894) avant d''être exilé en Martinique. Figure majeure de la résistance anticoloniale en Afrique de l''Ouest.', NOW()),
('Hubert Maga', '1916 – 2000', 'Premier président de la République du Dahomey (actuel Bénin) lors de l''indépendance proclamée le 1er août 1960. Originaire du nord du pays, il marqua les premières décennies politiques mouvementées du Bénin indépendant.', NOW()),
('Mathieu Kérékou', '1933 – 2015', 'Chef d''État du Bénin de 1972 à 1991 puis de 1996 à 2006. Il proclama en 1974 l''orientation marxiste-léniniste du pays, alors rebaptisé République populaire du Bénin, avant de conduire la transition démocratique lors de la Conférence nationale de février 1990.', NOW());

INSERT INTO periode_evolution_benin (titre, categorie, date_debut, date_fin, description, ordre_frise, created_at) VALUES
('Royaume d''Allada', 'royaume_precolonial', 1500, 1724, 'Royaume aja fondateur, dont seraient issues par scission les dynasties d''Abomey et de Porto-Novo selon la tradition orale.', 1, NOW()),
('Fondation du royaume du Danxomè', 'royaume_precolonial', 1625, 1625, 'Établissement de la capitale royale à Abomey sous le règne du roi Houégbadja, point de départ de près de trois siècles de royauté fon.', 2, NOW()),
('Apogée et commerce atlantique', 'royaume_precolonial', 1724, 1852, 'Expansion territoriale du Danxomè jusqu''à la côte, développement du commerce avec les puissances européennes, dont la traite négrière jusqu''à son abolition progressive au XIXe siècle.', 3, NOW()),
('Guerres franco-dahoméennes', 'colonisation', 1890, 1894, 'Résistance armée du roi Béhanzin face à la conquête coloniale française, aboutissant à l''annexion du royaume et à son intégration à l''Afrique-Occidentale française (AOF).', 4, NOW()),
('Dahomey colonial', 'colonisation', 1894, 1958, 'Administration coloniale française, développement des infrastructures portuaires et ferroviaires, émergence d''une élite politique et intellectuelle dahoméenne.', 5, NOW()),
('Indépendance', 'independance', 1960, 1960, 'Proclamation de l''indépendance de la République du Dahomey le 1er août 1960, sous la présidence d''Hubert Maga.', 6, NOW()),
('Instabilité politique et révolution marxiste', 'moderne', 1972, 1989, 'Prise de pouvoir du colonel Mathieu Kérékou en 1972 et proclamation de la République populaire du Bénin en 1975, orientée vers le marxisme-léninisme.', 7, NOW()),
('Renouveau démocratique', 'moderne', 1990, 1990, 'Conférence nationale de février 1990, l''une des premières d''Afrique francophone, ouvrant la voie à une transition pacifique vers le multipartisme et changeant le nom du pays en République du Bénin.', 8, NOW()),
('Bénin contemporain', 'moderne', 1991, 2026, 'Consolidation démocratique, développement des infrastructures, essor du tourisme culturel et mémoriel autour de la route de l''esclave et du patrimoine royal d''Abomey.', 9, NOW());

-- ---------------------------------------------------------------------
-- MODULE 5 — LANGUES ET MOTS-CLÉS
-- ---------------------------------------------------------------------
INSERT INTO langues (nom, zone_geographique, latitude_centre, longitude_centre, description) VALUES
('Fongbé', 'Zou, Atlantique, Littoral, Collines', 7.2000000, 2.0000000, 'Langue du groupe gbe, parlée par les Fon, langue véhiculaire la plus répandue du sud du Bénin.'),
('Yoruba', 'Ouémé, Plateau', 7.0000000, 2.7000000, 'Langue du groupe niger-congo, partagée avec les communautés yoruba du Nigéria voisin.'),
('Baatonum', 'Borgou, Alibori', 10.3000000, 3.2000000, 'Langue des Bariba, du groupe gur, parlée principalement autour de Nikki, Parakou et Kandi.'),
('Ditamari', 'Atacora', 10.1800000, 1.1000000, 'Langue des Betammaribè (Somba), du groupe gur, parlée dans la région de Boukoumbé et Natitingou.'),
('Fulfuldé', 'Nord du Bénin (Borgou, Alibori, Atacora)', 10.5000000, 2.5000000, 'Langue peule, parlée par les communautés pastorales du nord du pays, partagée avec les Peuls d''Afrique de l''Ouest.');

INSERT INTO mots_langue (langue_id, mot_expression, traduction_fr)
SELECT id, 'Kú do àfɔ̀n', 'Bonjour' FROM langues WHERE nom = 'Fongbé';
INSERT INTO mots_langue (langue_id, mot_expression, traduction_fr)
SELECT id, 'Á lɔ́ dò ganjí?', 'Comment vas-tu ?' FROM langues WHERE nom = 'Fongbé';
INSERT INTO mots_langue (langue_id, mot_expression, traduction_fr)
SELECT id, 'Ẹ n lẹ o', 'Bonjour (le matin)' FROM langues WHERE nom = 'Yoruba';
INSERT INTO mots_langue (langue_id, mot_expression, traduction_fr)
SELECT id, 'Wȍre', 'Merci' FROM langues WHERE nom = 'Baatonum';

-- ---------------------------------------------------------------------
-- MODULE 13 — ÉVÉNEMENTS SUPPLÉMENTAIRES (au-delà des 3 déjà en base)
-- ---------------------------------------------------------------------
INSERT INTO evenements (slug, titre, type_evenement, description, histoire_contexte, lieu_nom, latitude, longitude, ville, departement, date_debut, est_recurrent, frequence_recurrence, statut, entree_tarif, is_published, created_at, updated_at) VALUES
('fete-ignames-savalou', 'Fête des ignames', 'fete_traditionnelle', 'Cérémonie agraire célébrant le début de la récolte des ignames, ouverte par la dégustation rituelle du chef traditionnel avant que la population ne puisse consommer la nouvelle récolte.', 'Pratiquée dans plusieurs communautés du centre et du nord du Bénin (Savalou, Dassa, Bassila), cette fête marque symboliquement le renouveau agricole et le respect de l''autorité traditionnelle sur les ressources de la terre.', 'Place centrale de Savalou', 7.9280000, 1.9740000, 'Savalou', 'Collines', '2026-09-05', 1, 'annuel', 'a_venir', 'Gratuit', 1, NOW(), NOW()),
('marche-international-dantokpa', 'Grand marché de Dantokpa', 'marche_special', 'L''un des plus grands marchés à ciel ouvert d''Afrique de l''Ouest, où se croisent commerçants de tout le Bénin et des pays voisins.', NULL, 'Marché de Dantokpa', 6.3667000, 2.4360000, 'Cotonou', 'Littoral', '2026-08-01', 1, 'mensuel', 'a_venir', 'Gratuit', 1, NOW(), NOW());

-- ---------------------------------------------------------------------
-- MODULE 6 — QUESTIONS DE QUIZ
-- ---------------------------------------------------------------------
INSERT INTO quiz_questions (theme, question, reponse_a, reponse_b, reponse_c, reponse_d, bonne_reponse, explication, niveau) VALUES
('histoire', 'En quelle année le Bénin (alors Dahomey) a-t-il proclamé son indépendance ?', '1958', '1960', '1963', '1972', 'b', 'L''indépendance de la République du Dahomey a été proclamée le 1er août 1960.', 'facile'),
('histoire', 'Quel roi du Danxomè a mené la résistance armée contre la conquête coloniale française ?', 'Ghézo', 'Agaja', 'Béhanzin', 'Houégbadja', 'c', 'Béhanzin, dernier roi indépendant du Danxomè, résista aux troupes françaises lors des guerres franco-dahoméennes (1890-1894).', 'moyen'),
('histoire', 'Quelle ville fut la capitale du royaume du Danxomè ?', 'Porto-Novo', 'Ouidah', 'Abomey', 'Cotonou', 'c', 'Abomey fut fondée comme capitale royale vers 1625 sous le roi Houégbadja.', 'facile'),
('histoire', 'Quel événement marquant s''est tenu en février 1990 au Bénin ?', 'Un coup d''État militaire', 'La Conférence nationale', 'La création de la CEDEAO', 'Un accord commercial avec la France', 'b', 'La Conférence nationale de février 1990 a ouvert la voie au renouveau démocratique du Bénin.', 'moyen'),
('histoire', 'Quel corps militaire spécifique existait au sein de l''armée du royaume du Danxomè ?', 'Les Zouaves', 'Les Agojie (guerrières)', 'La Garde impériale', 'Les Tirailleurs', 'b', 'Les Agojie, corps de guerrières royales, constituaient une force militaire d''élite unique du royaume du Danxomè.', 'difficile'),

('traditions', 'Que signifie le mot "vodun" en fongbé ?', 'Roi', 'Esprit / force invisible', 'Guerrier', 'Marché', 'b', 'Vodun signifie littéralement « esprit » ou « force invisible », socle spirituel traditionnel des Fon.', 'facile'),
('traditions', 'Quel peuple est associé à l''architecture troglodyte des Tata ?', 'Les Fon', 'Les Yoruba', 'Les Somba (Betammaribè)', 'Les Dendi', 'c', 'Les Tata Somba, maisons-forteresses en terre, sont l''habitat traditionnel des Betammaribè de l''Atacora.', 'facile'),
('traditions', 'Que célèbre la fête du Gaani chez les Bariba ?', 'La récolte des ignames', 'La fin du jeûne, avec parades de cavaliers', 'L''arrivée de la saison des pluies', 'La naissance d''un roi', 'b', 'Le Gaani est une grande fête équestre et culturelle bariba célébrant la fin du jeûne.', 'moyen'),
('traditions', 'Quel animal est vénéré comme sacré au Temple des Pythons de Ouidah ?', 'Le crocodile', 'Le python royal', 'Le caméléon', 'La panthère', 'b', 'Le python royal (Dangbé) y est vénéré comme symbole de sagesse et de fertilité dans la tradition vodun.', 'facile'),
('traditions', 'Quelle cérémonie yoruba honore les ancêtres à travers des masques rituels ?', 'Le Sato', 'La sortie des Egungun', 'Le Dikuntri', 'Le Gaani', 'b', 'La sortie des Egungun est une cérémonie yoruba de commémoration des ancêtres à travers des danses masquées.', 'difficile'),

('langues', 'Quelle langue est la plus parlée dans le sud du Bénin, notamment autour d''Abomey ?', 'Le Baatonum', 'Le Fongbé', 'Le Ditamari', 'Le Fulfuldé', 'b', 'Le Fongbé est la langue des Fon, très largement parlée dans le Zou, l''Atlantique et le Littoral.', 'facile'),
('langues', 'Le Baatonum est la langue de quel groupe ethnique ?', 'Les Bariba', 'Les Adja', 'Les Yoruba', 'Les Peuls', 'a', 'Le Baatonum est parlé par les Bariba, principalement dans le Borgou et l''Alibori.', 'facile'),
('langues', 'Quelle langue peule est parlée dans le nord du Bénin ?', 'Le Dendi', 'Le Fulfuldé', 'Le Yoruba', 'L''Adja', 'b', 'Le Fulfuldé est la langue des Peuls (Fulani), présents dans le Borgou, l''Alibori et l''Atacora.', 'moyen'),
('langues', 'Comment dit-on "merci" en Baatonum ?', 'Kú do àfɔ̀n', 'Wȍre', 'Ẹ n lẹ o', 'Wǎ n xwé', 'b', '« Wȍre » signifie merci en Baatonum, la langue des Bariba.', 'difficile'),
('langues', 'La langue Ditamari est parlée principalement dans quelle région ?', 'L''Atacora (Boukoumbé)', 'Le Littoral', 'L''Ouémé', 'Le Mono', 'a', 'Le Ditamari est la langue des Betammaribè (Somba), parlée dans la région de Boukoumbé et Natitingou, en Atacora.', 'moyen');

-- ---------------------------------------------------------------------
-- MODULE 12 — ACTUALITÉS
-- ---------------------------------------------------------------------
INSERT INTO actualites (titre, slug, resume, contenu, source, publie_le, created_at) VALUES
('Le processus de restauration d''Abomey salué par l''UNESCO', 'restauration-abomey-unesco', 'Une mission d''évaluation confirme les progrès de la restauration des bas-reliefs royaux.', 'Dix ans après le dernier rapport de conservation, une mission d''évaluation de l''UNESCO s''est rendue sur le site des palais royaux d''Abomey pour constater l''avancée des travaux de restauration menés en partenariat avec l''École du Patrimoine Africain. Le rapport souligne particulièrement la qualité du travail réalisé sur les bas-reliefs historiés, restaurés selon des techniques traditionnelles de fabrication du banco.', 'unesco', NOW(), NOW()),
('Nouvelle fiche : les Tata Somba de l''Atacora', 'nouvelle-fiche-tata-somba', 'L''encyclopédie s''enrichit d''une fiche complète sur l''architecture troglodyte des Betammaribè.', 'Bénin Vivant publie une nouvelle fiche dédiée à l''architecture traditionnelle Tata Somba, incluant son histoire, sa fonction défensive historique, et son organisation spatiale liée aux rites initiatiques Dikuntri.', 'interne', NOW(), NOW()),
('Le ministère de la Culture lance un fonds de sauvegarde', 'fonds-sauvegarde-ministere', 'Un nouveau dispositif national vient compléter les projets communautaires portés par Bénin Vivant.', 'Le ministère du Tourisme, de la Culture et des Arts a annoncé le lancement d''un fonds national de sauvegarde du patrimoine, destiné à soutenir les initiatives de restauration à travers le pays, en complément des dispositifs de financement participatif existants.', 'officiel', NOW(), NOW());

-- ---------------------------------------------------------------------
-- MÉDIATHÈQUE, PARTENAIRES, FAQ
-- ---------------------------------------------------------------------
INSERT INTO mediatheque (titre, type, url, categorie, created_at) VALUES
('Palais royaux d''Abomey', 'image', '', 'sites', NOW()),
('Tissus appliqués royaux', 'image', '', 'ethnies', NOW()),
('Route des Pêches, Ouidah', 'image', '', 'sites', NOW()),
('Tata Somba, Boukoumbé', 'image', '', 'sites', NOW()),
('Marché de Bohicon', 'image', '', 'evenements', NOW()),
('Cité royale de Nikki', 'image', '', 'sites', NOW());

INSERT INTO partenaires (nom, logo_url, site_web, ordre, created_at) VALUES
('UNESCO', NULL, 'https://www.unesco.org', 1, NOW()),
('Ministère du Tourisme, de la Culture et des Arts du Bénin', NULL, NULL, 2, NOW()),
('École du Patrimoine Africain', NULL, 'https://epa-prema.net', 3, NOW()),
('Finanex — Digit''Héritage', NULL, NULL, 4, NOW()),
('FedaPay', NULL, 'https://fedapay.com', 5, NOW()),
('Institut Universitaire Les Cours Sonou', NULL, NULL, 6, NOW());

INSERT INTO faq (question, reponse, categorie, ordre) VALUES
('Bénin Vivant est-il gratuit ?', 'Oui, la consultation de l''encyclopédie, des sites, de la carte des langues et des quiz est entièrement gratuite. Seuls les dons vers les projets de sauvegarde sont volontaires.', 'Utilisation générale', 1),
('Comment fonctionne la géolocalisation des sites ?', 'Avec votre autorisation, le navigateur partage votre position pour calculer la distance vers chaque site historique en temps réel.', 'Utilisation générale', 2),
('Comment proposer une tradition ou un site ?', 'Créez un compte contributeur, puis utilisez le formulaire « Contribuer » depuis le menu. Chaque soumission est vérifiée par un modérateur avant publication.', 'Contribution & modération', 3),
('Combien de temps prend la validation d''une contribution ?', 'En général sous 3 à 5 jours ouvrés. Le statut est visible depuis votre espace personnel.', 'Contribution & modération', 4),
('Quels moyens de paiement sont acceptés pour les dons ?', 'MTN Mobile Money, Moov Money, et carte bancaire via FedaPay. Aucune donnée bancaire n''est stockée sur nos serveurs.', 'Dons & paiement', 5),
('Puis-je suivre l''utilisation de mon don ?', 'Oui, chaque projet affiche en temps réel le montant collecté sur son objectif, en toute transparence.', 'Dons & paiement', 6),
('Comment devenir guide touristique référencé ?', 'Inscrivez-vous en tant que guide, renseignez votre spécialité et vos langues parlées. Votre profil est validé par un administrateur avant publication.', 'Devenir guide ou administrateur', 7);


-- (comptes de démonstration : mot de passe "DemoGuide2026!" pour les 3,
--  uniquement utile pour les tests locaux — à supprimer avant mise en prod)
-- ---------------------------------------------------------------------
INSERT INTO users (uuid, nom, prenom, email, telephone, password_hash, role, email_verified_at, created_at, updated_at) VALUES
(UUID(), 'Agossou', 'Rachidatou', 'rachidatou.guide@example.com', '+229 97 00 00 01', '$2y$10$DumLnnP8Pjor/bAORwR6ge.YswA5ilgpf8u4xzYEPlRg4v7rnOriK', 'guide', NOW(), NOW(), NOW()),
(UUID(), 'Kora', 'Ismaël', 'ismael.guide@example.com', '+229 97 00 00 02', '$2y$10$DumLnnP8Pjor/bAORwR6ge.YswA5ilgpf8u4xzYEPlRg4v7rnOriK', 'guide', NOW(), NOW(), NOW()),
(UUID(), 'Dossou', 'Fabrice', 'fabrice.guide@example.com', '+229 97 00 00 03', '$2y$10$DumLnnP8Pjor/bAORwR6ge.YswA5ilgpf8u4xzYEPlRg4v7rnOriK', 'guide', NOW(), NOW(), NOW());

INSERT INTO guides_touristiques (user_id, specialite, langues_parlees, zone_couverte, telephone_pro, bio, statut, created_at)
SELECT id, 'Histoire royale et sites UNESCO', 'Fon, Français, Anglais', 'Abomey, Bohicon, Zou', '+229 97 00 00 01',
       'Guide certifiée depuis 8 ans, spécialisée dans l''histoire du royaume de Danxomè et les visites du site royal d''Abomey.',
       'valide', NOW()
FROM users WHERE email = 'rachidatou.guide@example.com';

INSERT INTO guides_touristiques (user_id, specialite, langues_parlees, zone_couverte, telephone_pro, bio, statut, created_at)
SELECT id, 'Route des esclaves et mémoire vodun', 'Fon, Français, Anglais, Espagnol', 'Ouidah, Grand-Popo', '+229 97 00 00 02',
       'Passionné d''histoire de la Route des Esclaves, propose des parcours mémoriels à Ouidah incluant la Porte du Non-Retour.',
       'valide', NOW()
FROM users WHERE email = 'ismael.guide@example.com';

INSERT INTO guides_touristiques (user_id, specialite, langues_parlees, zone_couverte, telephone_pro, bio, statut, created_at)
SELECT id, 'Architecture Tata Somba et randonnée', 'Ditamari, Fon, Français', 'Boukoumbé, Natitingou, Atacora', '+229 97 00 00 03',
       'Originaire de Boukoumbé, fait découvrir l''architecture troglodyte des Tata Somba et les paysages de la chaîne de l''Atacora.',
       'valide', NOW()
FROM users WHERE email = 'fabrice.guide@example.com';

-- =====================================================================
-- Fin du contenu de lancement.
-- =====================================================================
