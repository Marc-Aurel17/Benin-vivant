# Architecture complète — Bénin Vivant : Racines et Diversité

Ce document reprend **chaque module du cahier des charges** (section 4, Modules 1 à 12) et les
place dans une architecture complète à trois niveaux — public / espace connecté / admin —
sur le modèle de la plateforme ONG Gnonnan, adaptée et étendue au patrimoine béninois.

---

## 🌍 1. Partie publique (visiteurs, sans compte)

### Vitrine & contenu
- **Accueil** — hero, chiffres clés, mission, aperçu des modules, sites emblématiques, frise historique *(déjà maquetté)*
- **À propos** — présentation du projet, du concours Digit'Héritage, de l'équipe
- **Module 1 — Encyclopédie culturelle** : liste filtrable par région/ethnie *(déjà maquetté)* + fiche détaillée (histoire, religion/fétiche, gastronomie, danses, tenues, objets d'art, galerie)
- **Module 2 — Histoire et évolution du Bénin** : frise chronologique interactive *(déjà maquetté)*, page dédiée « Évolution moderne » (avant/après)
- **Module 3 — Sites historiques géolocalisés** : liste + carte + fiche détaillée *(déjà maquetté)* avec itinéraire temps réel
- **Module 5 — Carte des langues nationales** : carte interactive, mots-clés + audio par langue
- **Module 12 — Actualités patrimoine et culture** : liste + article détaillé (équivalent "Blog" côté Gnonnan)
- **Médiathèque** (photos/vidéos transverses à tous les sites/ethnies) + **Lightbox**
- **Partenaires** (institutions culturelles, UNESCO, mairies, sponsors du concours)
- **FAQ**
- **Carte interactive globale** (`/carte` + `/carte/data`) — vue d'ensemble : sites + zones linguistiques + signalements actifs, superposables en couches

### Module 13 — Événements traditionnels et culturels *(extension au-delà du cahier des charges)*

Ce module répond à un besoin réel non couvert par les 12 modules initiaux : savoir **où et quand**
assister à un événement traditionnel ou culturel (au-delà des sites figés).

**Réflexion sur la mécanique du module** :

- **Nature de l'événement** — un événement peut être :
  - rattaché à un site historique existant (Module 3), ex : cérémonie annuelle au Palais d'Abomey
  - rattaché à un groupe ethnique (Module 1), ex : Fête des ignames chez les Mahi
  - à un lieu libre non encore répertorié (ville/village + coordonnées manuelles)
- **Récurrence** — champ texte libre affiché (« chaque année en août », « tous les jeudis ») +
  une date concrète (`date_debut`/`date_fin`, `heure_debut`/`heure_fin`) pour l'occurrence à venir.
  Un moteur de récurrence complexe (calcul automatique des prochaines dates) est volontairement
  hors scope pour la deadline du concours — l'admin met à jour la prochaine date manuellement,
  ce qui reste réaliste vu la fréquence de ces événements (annuelle pour la plupart).
- **Types** : fête traditionnelle, cérémonie religieuse/vodun, festival culturel, marché
  périodique, événement institutionnel (ex : Digit'Héritage, journée du patrimoine).
- **Modération** — comme les contributions (Module 8) : soumission possible par un contributeur
  ou un guide touristique, publication après validation admin (`is_published`).
- **Découverte** — trois entrées : vue liste chronologique (prochains événements), vue calendrier
  mensuel, et couche supplémentaire sur la carte globale (Module 3/carte).
- **Alertes** — un visiteur peut s'abonner aux notifications par région et/ou type d'événement
  (table dédiée, réutilise le mécanisme newsletter mais avec des filtres).
- **Export calendrier** — bouton « Ajouter à mon calendrier » (fichier `.ics` généré à la volée)
  sur chaque fiche événement, pour que le visiteur ne l'oublie pas.
- **Liens transverses** — une fiche événement peut suggérer un guide touristique disponible sur
  la zone (Module 4) et le projet de sauvegarde associé s'il y en a un (Module 10).


- **Module 4 — Annuaire des guides touristiques** : liste, fiche guide, contact direct
- **Module 6 — Espace éducatif et ludique** : quiz thématiques (histoire/traditions/langues), badges, classement
- **Module 7 — Guide culturel intelligent (chatbot IA)** : widget accessible sur tout le site
- **Module 8 — Contribution communautaire** : formulaire de soumission (tradition / site / info) — équivalent "Proposer un projet" côté Gnonnan
- **Module 9 — Signalement de problème patrimonial** : formulaire + carte des signalements actifs
- **Module 10 — Projets de sauvegarde et de valorisation** : liste + fiche projet + formulaire de soumission (écoles/associations/communes)
- **Module 11 — Dons** : paiement en ligne MTN MoMo / Moov Money / FedaPay, page de remerciement, callback/webhook de confirmation, transparence des montants par projet
- **Module 13 — Événements traditionnels et culturels** *(extension au cahier des charges)* : calendrier des fêtes, cérémonies religieuses (ex. Vodun Days, Egungun, Gaani) et festivals, avec date/heure/lieu précis, rattachement optionnel à une ethnie ou un site existant, marqueur d'intérêt communautaire ("Ça m'intéresse"), export vers calendrier personnel (.ics), vue liste et vue calendrier mensuel

### Actions citoyennes transverses
- **Contact** (formulaire) + **Newsletter** (abonnement patrimoine/actualités)
- **Témoignages** — récits de visiteurs/habitants sur leur rapport au patrimoine (consultation + soumission)
- **Rejoindre la communauté** (CTA vers inscription)

### Espace contributeur (compte utilisateur public)
- Inscription, **vérification d'email par code**, connexion, déconnexion
- **Tableau de bord contributeur** : mes contributions (statut validation), mes signalements, mes badges/quiz, mes dons

### Espace guide touristique (compte spécifique)
- Inscription guide (spécialité, langues, zone couverte)
- **Tableau de bord guide** : demandes de contact reçues, profil public

### Inscription administrateur (public, contrôlée)
- Parcours en **3 étapes** : infos + vérif email par code → vérification d'identité → page d'attente → activation par lien
- Demande **approuvée par le super admin** avant activation

---

## 🔑 2. Partie admin (panneau de gestion)

**Tableau de bord** — vue d'ensemble (contributions en attente, signalements ouverts, dons du mois, contenu publié) + **recherche globale**

### Gestion de contenu (CRUD complet)
| Module (cahier des charges) | Actions |
|---|---|
| Groupes ethniques (Module 1) | ajouter / modifier / dépublier / supprimer + médias |
| Événements (Module 13) | ajouter / modifier / publier / annuler / supprimer + médias |
| Sites historiques (Module 3) | ajouter / modifier / publier / supprimer + médias |
| Figures & périodes historiques (Module 2) | ajouter / modifier / supprimer |
| Langues & mots-clés (Module 5) | ajouter / modifier / supprimer + audio |
| Guides touristiques (Module 4) | valider / suspendre / supprimer |
| Quiz & badges (Module 6) | ajouter / modifier / activer-désactiver questions |
| Projets de sauvegarde (Module 10) | ajouter / modifier / clôturer |
| Actualités (Module 12) | ajouter / modifier / supprimer |
| Médiathèque | ajouter / supprimer |
| Partenaires | ajouter / modifier / supprimer |
| Témoignages | ajouter / voir / publier ou dépublier / supprimer |
| Rapports (dossier de soumission, bilans) | ajouter / supprimer |

### Gestion des interactions publiques
- **Contributions communautaires** (Module 8) — valider / rejeter / commenter
- **Signalements** (Module 9) — consulter, changer le statut, supprimer, voir sur carte
- **Propositions de projets** (Module 10) — consulter, changer le statut
- **Dons** (Module 11) — liste, détail, statut (suivi FedaPay/MoMo/Moov)
- **Contacts** reçus — consulter, supprimer
- **Abonnements newsletter** — liste, suppression

### Gestion des comptes
- **Contributeurs** — liste, fiche, changer statut, supprimer
- **Guides touristiques** — liste, fiche, valider/suspendre
- **Comptes administrateurs** *(super admin uniquement)* — créer / modifier / activer-désactiver / supprimer
- **Demandes d'inscription admin** *(super admin uniquement)* — approuver, rejeter, supprimer, lever un blocage

### Compte personnel
- Profil admin — modifier infos, changer mot de passe, changer d'email (avec confirmation)
- **Espace Super Admin sécurisé** (`/admin/super`) — modification directe avec **re-saisie obligatoire du mot de passe actuel**

### Système & diagnostic
- **Logs d'activité** (table `audit_logs`, déjà présente dans le schéma)
- **Diagnostic FedaPay/MoMo/Moov** — tester la connexion API paiement
- **Diagnostic Email** — tester l'envoi SMTP réel
- **Diagnostic microservice IA** (`/health` du service Python, Module 7)

---

## 🎨 3. Ce que le Super Admin peut personnaliser (`/admin/parametres`)

*Réservé exclusivement au rôle `super_admin`.*

**Identité du site**
- Nom du site, slogan, logo (upload)
- Email de contact, téléphone, adresse
- Réseaux sociaux : WhatsApp, Facebook, Instagram, TikTok, YouTube, lien Google Maps

**Thème visuel**
- Thème clair/sombre par défaut
- Couleur principale & couleur d'accent (remplace nos variables `--or` / `--vert-patine` par défaut)
- Police de caractères (avec validation anti-injection — voir sécurité)
- Taille de police de base, taille des titres, interligne

**Contenu dynamique**
- Texte du bandeau d'accueil (hero), texte de la section mission
- Message newsletter, mentions légales
- **Statistiques affichées** en page d'accueil (ethnies, sites, modules...) + libellés personnalisables

Techniquement : ces réglages sont stockés dans une table `site_settings` (clé/valeur) et injectés
au chargement de chaque page publique sous forme de variables CSS (`:root{--primary:...}`) —
voir `config/theme.php` plus bas.

---

## 🖼️ 4. Texture / présentation générale (déjà amorcée dans nos maquettes)

- Design éditorial sobre, palette patrimoine béninois (indigo nuit, or, rouge-vodun, vert-patine), personnalisable par le super admin
- Mode clair/sombre avec bascule
- Icônes en **SVG inline** dessinées à la main (pas de sticker/emoji, pas de librairie lourde)
- Animations douces (fade-in au scroll, GSAP), motif géométrique appliqué (bandeau signature)
- Cartes (cards) pour ethnies, sites, projets, actualités — cohérentes sur tout le site
- Menu responsive burger mobile, sidebar admin rétractable
- Formulaires soignés, messages flash succès/erreur
- Lightbox pour la médiathèque
- Typographie configurable (police, taille, interligne) depuis le super admin

---

## Correspondance avec le cahier des charges — roadmap

| Semaine (cahier des charges) | Contenu couvert |
|---|---|
| **Semaine 1** *(fait)* | BDD, auth PHP, Module 1 (Encyclopédie), Module 3 (Sites + GPS), Module 9 (Signalements), maquettes front |
| **Semaine 2** | Itinéraire GPS complet, Module 5 (Langues), Module 6 (Quiz/badges), Module 4 (Guides), Module 10 (Projets), Module 12 (Actualités) + **tout le panneau admin/super-admin décrit ci-dessus** |
| **Derniers jours** | Module 7 (Chatbot IA), PWA, Module 11 (Dons/paiement), diagnostics système, finitions, vidéo de présentation |

Ce document sert de référence unique : chaque table SQL, chaque endpoint PHP et chaque page
front qu'on construit ensuite s'y rattache explicitement à un module du cahier des charges.
