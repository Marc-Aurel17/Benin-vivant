# Bénin Vivant : Racines et Diversité

Concours **Digit'Héritage by Finanex** — Édition Indépendance du Bénin

Plateforme numérique de valorisation du patrimoine culturel, historique et touristique du Bénin.
Stack : **PHP natif (PDO)** + **MySQL** + **HTML/CSS/JavaScript** + **Python** (microservice IA),
sans framework — pensé pour tourner sous **XAMPP**.

---

## 🧪 Tu veux juste tester le site maintenant ?

**Va directement dans `docs/GUIDE-DE-TEST.md`**.

---

## 📁 Structure du projet

```
Benin-Vivant-Projet-Complet/
├── README.md
├── docs/                 GUIDE-DE-TEST, ARCHITECTURE, SECURITE,
│                         MODULE-EVENEMENTS, TEST-LOCAL-FEDAPAY, PASSAGE-EN-LIVE
├── database/
│   ├── schema.sql        structure complète — à importer en premier
│   └── contenu_reel.sql  contenu réel + comptes de test — ensuite
├── backend-php/          à copier dans htdocs/backend-php/ — 93 fichiers PHP
├── ai-service/           microservice Python (chatbot IA)
├── frontend/             à copier dans htdocs/frontend/ — 50 pages
└── dossier-soumission/   dossier écrit + script vidéo
```

## 🔑 Comptes de test

| Rôle | Email | Mot de passe |
|---|---|---|
| Super admin | `admin@benin-vivant.bj` | `BeninVivant2026!` |
| Guide | `rachidatou.guide@example.com` | `DemoGuide2026!` |
| Guide | `ismael.guide@example.com` | `DemoGuide2026!` |
| Guide | `fabrice.guide@example.com` | `DemoGuide2026!` |

⚠️ Change le mot de passe admin dès la première connexion.

---

## 📋 État des lieux — panneau admin complet, 100% fonctionnel

Chaque module du cahier des charges a sa page de gestion admin dédiée
(CRUD réel) et son parcours public complet :

| Module | Page publique | Page admin |
|---|---|---|
| 1 — Encyclopédie | `encyclopedie.html` | `admin-contenu.html` |
| 2 — Histoire et évolution | (frise sur `index.html`) | `admin-histoire.html` |
| 3 — Sites historiques | `site-detail.html`, `carte.html` | `admin-contenu.html` |
| 4 — Guides touristiques | `guides.html`, `guide-detail.html` | `admin-guides.html` |
| 5 — Carte des langues | `langues.html` | `admin-langues.html` |
| 6 — Quiz & badges | `quiz.html` | `admin-quiz.html` |
| 7 — Chatbot IA | `assistant.html` | `admin-diagnostic.html` |
| 8 — Contributions | `contribuer.html` | `admin-contributions.html` |
| 9 — Signalements | `signalement.html` | `admin-signalements.html` |
| 10 — Projets & propositions | `projets.html`, `proposer-projet.html` | `admin-projets.html`, `admin-propositions.html` |
| 11 — Dons | `dons.html`, `merci.html` | `admin-dons.html` |
| 12 — Actualités | `actualites.html` | `admin-actualites.html` |
| 13 — Événements | `evenements.html` | `admin-evenements.html` |
| Témoignages | `temoignages.html` | `admin-temoignages.html` |
| Médiathèque | `mediatheque.html` | `admin-mediatheque.html` |
| Partenaires | `partenaires.html` | `admin-partenaires.html` |
| Contacts & newsletter | `contact.html` | `admin-messages.html` |
| Comptes & inscriptions | `mon-espace.html`, `devenir-admin.html` | `admin-comptes.html`, `admin-demandes.html`, `admin-contributeurs.html` |
| Thème du site | — | `super-admin-theme.html` |

**~48 pages sur 50 connectées à l'API réelle.** Seules `index.html` et
`a-propos.html` restent en contenu éditorial statique (pages de présentation
sans données variables — c'est voulu).

## ✅ Recherche globale — désormais fonctionnelle

`api/admin/recherche-globale.php` interroge en une fois sites, ethnies,
événements, actualités, projets, contributions, signalements, comptes
(guides/contributeurs/admin) et partenaires, et renvoie des résultats
catégorisés avec lien direct vers la bonne page admin. Barre de recherche du
dashboard branchée avec anti-rafale (debounce) — plus aucune fonctionnalité
décorative dans le panneau admin.

Tout validé avant livraison : `php -l` sur les 93 fichiers PHP, syntaxe JS
sur les 50 pages, aucun lien mort.

## 🚀 Installation rapide

1. Copie `backend-php/` → `htdocs/backend-php/` et `frontend/` → `htdocs/frontend/`
2. Démarre Apache + MySQL (XAMPP)
3. Importe `schema.sql` puis `contenu_reel.sql` dans une base `benin_vivant`
4. Vérifie `backend-php/config/database.php`
5. `http://localhost/frontend/index.html`

## 💳 Paiement (Module 11)

Voir `docs/TEST-LOCAL-FEDAPAY.md` et `docs/PASSAGE-EN-LIVE.md`.

## 🤖 Chatbot IA (Module 7)

Branché sur le vrai contenu vérifié. Configure `ai-service/.env`.

## 🔒 Sécurité

Voir `docs/SECURITE.md`.
