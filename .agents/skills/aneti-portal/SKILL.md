---
name: aneti-portal
description: >-
  Expert guide for the ANETI International Drupal portal codebase.
  Use whenever working on the ANETI portal, custom modules (aneti_*, unique_*, email_updater),
  the job_board custom theme, Content Moderation workflows, testing with test_aneti_features.php,
  or managing configuration and DDEV environments without scanning the whole repository.
---

# Guide Expert du Portail ANETI International

Ce skill fournit un accès direct à toutes les connaissances métier, techniques et architecturales du portail de l'Agence Nationale pour l'Emploi et le Travail Indépendant (ANETI International) sous Drupal 9.5.11 / PHP 8.1.

Il est conçu pour orienter immédiatement l'agent vers les bons fichiers sans lancer de scans globaux coûteux.

---

## 1. Localisation Directe des Composants du Portail

Lorsque vous devez intervenir sur un élément, consultez directement les fichiers cibles :

### A. Modules Custom (`web/modules/custom/`)
- **Règles d'inscription & Profil Candidat** : `web/modules/custom/aneti_candidat/aneti_candidat.module`
- **Gestion des CV** : `web/modules/custom/aneti_cv/aneti_cv.module`
- **Gestion des Entreprises** : `web/modules/custom/aneti_entreprise/aneti_entreprise.module`
- **Plugins Rules & Workflows** :
  - Condition `UserHasRole` : `web/modules/custom/aneti_enhance/src/Plugin/Condition/UserHasRole.php`
  - Action `RoleEmailAction` : `web/modules/custom/aneti_enhance/src/Plugin/RulesAction/RoleEmailAction.php`
  - Action `SubscribeUserAction` : `web/modules/custom/aneti_enhance/src/Plugin/RulesAction/SubscribeUserAction.php`
  - Presave et notification : `web/modules/custom/aneti_enhance/aneti_enhance.module`
- **Unicité CIN & Postulation** :
  - `web/modules/custom/unique_cin/src/Plugin/Validation/Constraint/UniqueCinConstraintValidator.php`
  - `web/modules/custom/unique_postulation/unique_postulation.module`

### B. Thème Graphique Custom (`web/themes/custom/job_board/`)
- Configuration du thème : `web/themes/custom/job_board/job_board.info.yml`
- Préprocesseurs et logique PHP : `web/themes/custom/job_board/job_board.theme`
- Bibliothèques CSS/JS : `web/themes/custom/job_board/job_board.libraries.yml`
- Templates Twig : `web/themes/custom/job_board/templates/`
  - Nœuds : `node--offre.html.twig`, `node--curriculum_vitae.html.twig`
  - Pages : `page--user--login.html.twig`, `page--contact.html.twig`

### C. Gestion des Configurations & Environnements
- Configuration globale de production : `config/sync/`
- Configuration spécifique au dev (Devel, etc.) : `config/split/dev/`
- Bascule dynamique du split dev : `web/sites/default/settings.php` via variable `ENVIRONMENT` dans `.env`
- Clés de sécurité JWT : `certificates/jwt.key.txt`

---

## 2. Procédures et Commandes Clés

### A. Recompilation des Caches (Obligatoire après modification de plugins/annotations)
```bash
ddev drush cr
```

### B. Exécution du Banc de Test Fonctionnel (19 Assertions)
Toujours exécuter ce script pour valider qu'aucune régression n'est introduite sur les parcours Candidat, Entreprise, Offres, Postulations ou Webforms :
```bash
ddev drush scr scripts/test_aneti_features.php
```

### C. Synchronisation et Export de Configuration
```bash
# Vérifier les deltas de configuration
ddev drush cst

# Exporter proprement vers config/sync/ et config/split/dev/
ddev drush cex -y

# Importer les configurations modifiées
ddev drush cim -y
```

---

## 3. Workflows Éditoriaux (Content Moderation)

Le portail gère 5 workflows critiques :

1. **`candidat`** (Profil demandeur d'emploi) :
   - États : `draft`, `published`, `archived`
   - *Règle* : Tout profil doit être en état `published` (`$profile->setDefault(TRUE)`) pour être associé activement au compte utilisateur.
2. **`entreprise`** (Profil recruteur) :
   - États : `draft`, `need_review`, `published`, `blacklisted`, `archived`
3. **`offre`** (Offre d'emploi déposée par une entreprise) :
   - États : `draft` ➔ `need_review` (déclenche `role_email_action` vers webmasters) ➔ `published` ➔ `unpublished`
4. **`postulation`** (Candidature d'un usager à une offre) :
   - États : `draft` ➔ `pending` ➔ `preselect_referent` ➔ `preselected` ➔ `published` (acceptée) / `archived`
5. **`cv`** (Curriculum Vitae numérique) :
   - États : `draft`, `published`

---

## 4. Règles d'Optimisation des Recherches

Pour éviter tout ralentissement lors du développement :
- **NE JAMAIS** scanner récursivement la racine avec des commandes génériques.
- Privilégier les ciblages précis :
  ```bash
  # Exemple pour rechercher dans les modules custom
  grep -rn "ma_chaine" web/modules/custom/

  # Exemple pour rechercher dans le thème
  grep -rn "ma_chaine" web/themes/custom/job_board/

  # Exemple pour rechercher dans les configurations
  grep -rn "ma_chaine" config/sync/
  ```

