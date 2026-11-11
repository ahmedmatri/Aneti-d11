# ANETI International — Directives Agent & Guide d'Architecture

Ce document fournit à l'agent Antigravity toutes les connaissances structurelles du portail ANETI afin d'éviter les scans coûteux et répétitifs de l'ensemble du projet.

---

## ⚡ RÈGLE D'OR : ÉVITER LES SCANS GLOBAUX
- **INTERDICTION** d'exécuter des recherches globales non ciblées telles que `grep -rn "..." .` ou `find .` sans exclusions strictes.
- **Dossiers à toujours exclure** des recherches : `vendor/`, `web/core/`, `.git/`, `.ddev/`, `node_modules/`, `web/sites/default/files/`.
- Le code spécifique au projet se concentre **exclusivement** dans :
  - `web/modules/custom/` (Modules sur mesure)
  - `web/themes/custom/job_board/` (Thème actif)
  - `config/sync/` et `config/split/dev/` (Configurations)
  - `scripts/` (Scripts d'automatisation et de test)
  - `Docs/` (Documentation technique du projet)

---

## 🗺️ Cartographie de l'Arborescence du Projet

```text
aneti-prod 3/
├── .env                          # Configuration d'environnement locale (ENVIRONMENT=dev)
├── .env.example                  # Modèle d'environnement versionné (ENVIRONMENT=prod)
├── AGENTS.md                     # Directives et index d'architecture agent (ce fichier)
├── composer.json                 # Gestionnaire de dépendances Composer
├── config/
│   ├── sync/                     # Configurations exportées pour la Production (sans dev)
│   └── split/dev/                # Configuration isolée de Développement (Devel, etc.)
├── certificates/                 # Clés JWT pour l'authentification API (jwt.key.txt)
├── Docs/                         # Documentation officielle et rapports du projet
│   ├── bilan_technique_upgrade_drupal9.md
│   ├── rapport_tests_fonctionnels_d9.md
│   ├── rapport_actions_detaillees.md
│   ├── script_upgrade_drupal9.md
│   └── script_nettoyage_bdd.md
├── patches/                      # Correctifs Composer (patch multiple_registration)
├── scripts/
│   ├── 1-cleanup_db_pre_upgrade.sh  # Script de nettoyage préalable de la BDD
│   ├── 2-upgrade_drupal9.sh         # Script d'upgrade automatisé Drupal 9
│   └── test_aneti_features.php      # Banc de test automatisé 19/19 (Drush scr)
└── web/
    ├── libraries/                # chosen, jquery-ui-touch-punch
    ├── modules/
    │   ├── contrib/              # Modules communautaires gérés par Composer
    │   └── custom/               # 9 Modules Custom ANETI
    ├── sites/default/
    │   ├── settings.php          # Configuration Drupal (Dotenv + Config Split)
    │   └── settings.ddev.php     # Configuration DDEV (base de données MariaDB)
    └── themes/custom/
        └── job_board/            # Thème personnalisé actif du portail
```

---

## 🧩 Les 9 Modules Custom (`web/modules/custom/`)

| Module | Emplacement | Responsabilité Métier |
| :--- | :--- | :--- |
| **`aneti_candidat`** | `web/modules/custom/aneti_candidat` | Gestion des profils candidats, présave des codes postaux/gouvernorats, formulaires d'inscription. |
| **`aneti_cv`** | `web/modules/custom/aneti_cv` | Gestion du CV numérique (`curriculum_vitae`), compétences, expériences, blocs CV. |
| **`aneti_entreprise`**| `web/modules/custom/aneti_entreprise`| Gestion des profils entreprises, champs recruteurs, suivi d'offres. |
| **`aneti_enhance`** | `web/modules/custom/aneti_enhance` | Coeur custom : plugins Rules (`UserHasRole`, `RoleEmailAction`), liaisons automatiques auteur/nœud, workflows de modération. |
| **`aneti_emails`** | `web/modules/custom/aneti_emails` | Templates et gestion de l'envoi des courriels métier. |
| **`aneti_grc`** | `web/modules/custom/aneti_grc` | Gestion de la relation citoyenne / usagers ANETI. |
| **`email_updater`** | `web/modules/custom/email_updater` | Mise à jour et validation des adresses e-mail des comptes. |
| **`unique_cin`** | `web/modules/custom/unique_cin` | Validation d'unicité du numéro CIN (Carte d'Identité Nationale tunisienne) sur les profils. |
| **`unique_postulation`**| `web/modules/custom/unique_postulation`| Règle d'unicité empêchant un candidat de postuler plusieurs fois à la même offre. |

---

## 🎨 Thème Actif

- **Nom** : `job_board`
- **Emplacement** : [`web/themes/custom/job_board/`](file:///Users/irtam/Desktop/Drupal/aneti-prod%203/web/themes/custom/job_board)
- **Moteur** : Twig / Bootstrap 4 / Sass
- **Templates** : `web/themes/custom/job_board/templates/`

---

## 🛠️ Commandes Fréquentes (DDEV & Drush)

Ne jamais tenter d'exécuter `php` ou `composer` directement sur l'hôte si indisponibles : toujours préfixer par `ddev` :

```bash
# Vider et recompiler tous les caches Drupal
ddev drush cr

# Exécuter le banc de test fonctionnel (19 assertions automatisées)
ddev drush scr scripts/test_aneti_features.php

# Exporter / Importer la configuration Drupal
ddev drush cex -y
ddev drush cim -y

# Vérifier les différences de configuration
ddev drush cst

# Mettre à jour la base de données
ddev drush updb -y

# Statut du site et modules
ddev drush status
ddev drush pml --type=module --status=enabled
```

---

## ⚖️ Entités et Workflows Éditoriaux Clés

- **Types de Profils (`profile`)** : `candidat`, `entreprise`, `referent`, `chef_de_bureau`, `partenaire`.
- **Types de Nœuds (`node`)** : `curriculum_vitae`, `offre`, `postulation`, `actualite`, `evenement`.
- **États de Modération (`content_moderation`)** :
  - Offre : `draft` ➔ `need_review` ➔ `published` / `unpublished`.
  - Postulation : `pending` ➔ `preselect_referent` ➔ `preselected` ➔ `published` (acceptée) / `archived`.
  - Profils : assujettis aux workflows modérés (nécessitent `moderation_state = published` pour visibilité active).

