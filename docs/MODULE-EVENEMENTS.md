# Module 13 — Événements traditionnels et culturels

Module ajouté au-delà du cahier des charges initial, car il répond à un vrai besoin :
savoir *quoi voir, où, et quand* — complément naturel aux Modules 1, 3 et 4.

## Ce qu'un événement doit porter

Un événement béninois type (Fête des Ignames, Gaani, Vodun Days, Fête de la Génération...)
a des caractéristiques particulières qu'il faut modéliser correctement :

- **Localisation** : soit un site historique existant (FK vers `sites_historiques`), soit une
  ville/commune libre — un événement n'a pas toujours un "site" au sens du Module 3
- **Lien culturel** : rattachement optionnel à un groupe ethnique (Module 1), pour que la fiche
  ethnie affiche "événements à venir chez les Bariba"
- **Date et heure** : date de début, date de fin (certains durent plusieurs jours), heure si pertinente
- **Récurrence** : la plupart de ces événements reviennent chaque année à peu près à la même
  période (ex. Gaani à Nikki, Vodun Days le 10 janvier). Plutôt que de construire un moteur de
  règles de récurrence complexe (type RRULE iCal) pour la V1, on stocke :
  - une instance concrète par édition (date précise de cette année)
  - un champ texte libre `frequence_indicative` ("chaque année en janvier") pour l'affichage
  - un champ `evenement_parent_id` optionnel pour relier les éditions successives entre elles
  Ça reste simple à administrer (l'admin crée "Gaani 2027" en copiant "Gaani 2026") tout en
  donnant au visiteur l'information "c'est récurrent, revenez l'an prochain".
- **Statut calculé** : à_venir / en_cours / terminé — calculé automatiquement à partir de la date
  du jour plutôt que stocké et à maintenir manuellement (sauf "annulé", qui lui doit être explicite)
- **Type d'événement** : festival, cérémonie religieuse/vodun, fête agricole, marché traditionnel,
  concours/compétition, commémoration — pour permettre le filtrage
- **Modération** : comme tout contenu communautaire, `is_published` avec validation admin si
  soumis par un contributeur ou une commune/association

## Intégrations avec l'existant

- **Carte interactive** (`carte.html`) : nouvelle couche "Événements à venir", marqueurs avec une
  icône calendrier distincte des sites historiques
- **Fiche site** (`site-detail.html`) : section "Événements associés à ce site"
- **Fiche ethnie** (`encyclopedie.html` → fiche détail) : section "Événements de ce peuple"
- **Accueil** (`index.html`) : bandeau "Prochains événements" (3 plus proches dans le temps)
- **Newsletter** : les abonnés reçoivent un rappel avant un événement à venir dans leur région
  (nécessite de connaître la localisation de l'abonné — V2, hors scope immédiat)
- **Guides touristiques** (Module 4) : un événement peut lister les guides disponibles ce jour-là

## Vue publique : liste + calendrier

Deux façons de parcourir les événements, activées par un même jeu de données :
- **Vue liste** filtrable (type, région, période) — pour "je cherche un type d'événement"
- **Vue calendrier mensuel** — pour "qu'est-ce qui se passe ce mois-ci", avec les jours marqués

## Modèle de données

```
evenements
├── id, slug, titre, description
├── type_evenement       ENUM('festival','ceremonie_religieuse','fete_agricole',
│                              'marche_traditionnel','concours','commemoration','autre')
├── site_historique_id   FK nullable → sites_historiques
├── groupe_ethnique_id   FK nullable → groupes_ethniques
├── ville, departement, latitude, longitude
├── date_debut, date_fin (DATE)
├── heure_debut, heure_fin (TIME, nullable)
├── frequence_indicative VARCHAR   -- "Chaque année en janvier" (affichage seulement)
├── evenement_parent_id  FK nullable → evenements (relie les éditions successives)
├── organisateur         VARCHAR   -- commune, association, chefferie traditionnelle
├── tarif                VARCHAR   -- "Accès libre" / "2 000 FCFA"
├── image_couverture
├── statut                ENUM('a_venir','en_cours','termine','annule')  -- 'annule' seul est manuel,
│                                                                            le reste est recalculé à l'affichage
├── is_published, created_by, created_at, updated_at
```

Le statut affiché (à_venir / en_cours / terminé) est recalculé côté PHP à chaque lecture en
comparant `date_debut`/`date_fin` à `NOW()` — sauf si `statut = 'annule'`, qui prime toujours.
Ça évite un job cron pour mettre à jour des milliers de lignes chaque nuit.
