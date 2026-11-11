---
name: aneti-testing-qa
description: >-
  Expert guide for executing, maintaining, and extending automated functional tests
  and quality assurance on the ANETI portal.
  Use whenever running regression tests, writing new test cases in scripts/test_aneti_features.php,
  verifying user journeys (Candidat, Entreprise, Moderation, Webform),
  or validating database integrity after code changes.
---

# Guide QA & Tests Fonctionnels Automatisés — ANETI

Ce skill détaille les procédures d'assurance qualité et d'exécution du banc de test automatisé du portail de l'ANETI International sous Drupal 9/10.

---

## 1. Règle Fondamentale : Intégrité de la Base de Données

Le portail ANETI contient plus de **318 000 comptes utilisateurs réels** et plus d'un million de nœuds et profils en base de données.
- **Règle absolue** : Tout test automatisé ou script de validation DOIT nettoyer systématiquement (Rollback hygiénique) chaque entité créée (`User`, `Profile`, `Node`, `WebformSubmission`).
- **Préfixage obligatoire** : Toute donnée de test doit inclure un suffixe aléatoire ou timestamp (ex: `test_suffix = (string) mt_rand(1000000000, 2000000000)`).

---

## 2. Exécution Rapide du Banc de Test

```bash
# Recompiler les caches avant le test
ddev drush cr

# Lancer les 19 assertions métier
ddev drush scr scripts/test_aneti_features.php
```

### Critère de Succès Obligatoire :
```text
  • Total des assertions : 19
  • Assertions réussies  : 19 / 19
  • Échecs               : 0
🎉 TOUTES LES FONCTIONNALITÉS MÉTIER SONT 100% OPÉRATIONNELLES !
```

---

## 3. Périmètre des Tests Couverts (10 Domaines)

1. **Authentification Candidat** :
   - Création compte rôle `candidat`.
   - Validation via le service `user.auth`.
2. **Profil Candidat** :
   - Création de l'entité `profile: candidat` avec champs (`field_nom`, `field_prenom`, `field_cin`).
   - Publication via `content_moderation` (`moderation_state = published`) et `$profile->setDefault(TRUE)`.
   - Vérification du chargement via `loadByUser($account, 'candidat')`.
3. **Curriculum Vitae (CV)** :
   - Création du nœud `curriculum_vitae` rattaché au candidat.
   - Contrôle du hook `aneti_enhance_entity_presave` (attribution automatique de `field_candidat`).
4. **Entreprise & Recruteur** :
   - Création compte rôle `entreprise`.
   - Création du profil `profile: entreprise` (`field_nom_de_l_entreprise`).
5. **Workflow Offre d'Emploi** :
   - Création en brouillon (`draft`).
   - Transition vers `need_review` (déclenche la règle Rules `role_email_action`).
   - Approbation finale vers `published`.
6. **Postulation / Candidature** :
   - Création de la liaison candidat ➔ offre ➔ CV (`node: postulation`).
   - Passage des états de modération : `pending` ➔ `preselected` ➔ `published`.
7. **Relations Citoyennes & Webform** :
   - Soumission d'une réclamation réelle via `WebformSubmission::create()`.
   - Vérification du handler d'email et de l'enregistrement de l'ID de réclamation.
8. **Moteurs de Recherche Vues** :
   - Exécution de la vue d'offres `loffre` (page de listing paginée).
   - Exécution de la vue `recherche_cv` (moteur de filtrage des profils).
9. **Support Multilingue** :
   - Vérification de la disponibilité des 4 langues : Français (`fr`), Arabe (`ar`), Anglais (`en`), Italien (`it`).
10. **Rollback & Sécurité BDD** :
    - Suppression ordonnée : Soumissions Webform ➔ Nœuds ➔ Profils ➔ Utilisateurs.
    - Vérification qu'aucune entité test ne subsiste en base.

---

## 4. Comment Ajouter un Nouveau Cas de Test

Pour ajouter une nouvelle assertion dans [`scripts/test_aneti_features.php`](file:///Users/irtam/Desktop/Drupal/aneti-prod%203/scripts/test_aneti_features.php) :

```php
// 1. Instancier l'entité ou exécuter l'action
$success = (bool) my_business_function();

// 2. Enregistrer l'entité pour le rollback
$created_entities['nodes'][] = $new_node->id();

// 3. Enregistrer l'assertion dans le tableau $results
record_test($results, '11.1', "Description de l'action métier", $success, "Détail ou métrique observée");
```

