# Guide d'Utilisation : Script Automatisé d'Upgrade Drupal 9 (`drupal9-upgrade.sh`)

Ce document décrit le fonctionnement, les étapes et l'utilisation du script automatisé d'upgrade vers Drupal 9 : [`scripts/2-upgrade_drupal9.sh`](file:///Users/irtam/Desktop/Drupal/aneti-prod%203/scripts/2-upgrade_drupal9.sh) (accessible également via le raccourci [`scripts/drupal9-upgrade.sh`](file:///Users/irtam/Desktop/Drupal/aneti-prod%203/scripts/drupal9-upgrade.sh)).

---

## 1. Rôle et Objectif du Script

Ce script est conçu pour être exécuté sur n'importe quel environnement (machine de dev locale ou serveur de recette) dès lors qu'un dump de base de données Drupal 8 de production vient d'être importé sur le codebase Drupal 9.

Il orchestre l'ensemble des opérations requises en assurant :
1. **La sécurité des données** : aucune donnée métier n'est supprimée ; les 318 075 comptes candidats et inscrits sont 100 % préservés.
2. **La prévention des blocages connus** :
   * Neutralisation du produit cartésien SQL de `multiple_registration_update_8204()` (101 milliards de comparaisons évitées).
   * Désactivation des notifications SMTP externes pour éviter les blocages de timeout réseau.
   * Nettoyage des résidus orphelins liés à l'ancien thème `aneti_2019` pour permettre un `drush cim` sans friction.
3. **L'enchaînement strict dans les règles de l'art Drupal** :
   `Nettoyage préalable ➔ drush updatedb ➔ Claro ➔ drush cim ➔ drush cr`.

---

## 2. Syntaxe & Options

Le script peut être lancé depuis la machine hôte ou directement à l'intérieur du conteneur DDEV :

```bash
# Exécution interactive (demande confirmation)
./scripts/drupal9-upgrade.sh

# Exécution automatisée (idéale en CI/CD ou script de restore)
./scripts/drupal9-upgrade.sh -y
```

### Options disponibles :

| Option | Description |
| :--- | :--- |
| `-y`, `--yes` | Valide automatiquement les confirmations interactives. |
| `--skip-cleanup` | Ne lance pas automatiquement le script de nettoyage préalable [`scripts/1-cleanup_db_pre_upgrade.sh`](file:///Users/irtam/Desktop/Drupal/aneti-prod%203/scripts/1-cleanup_db_pre_upgrade.sh). |
| `-h`, `--help` | Affiche l'aide et la liste des arguments. |

---

## 3. Détail des 7 Étapes Exécutées par le Script

```mermaid
flowchart TD
    E1["[1/7] Contrôle BDD & Nettoyage<br>Appel automatique 1-cleanup_db_pre_upgrade.sh"] --> E2["[2/7] Neutralisation Réseau<br>smtp_on = false & config_log mail = 0"]
    E2 --> E3["[3/7] Sécurisation Données<br>Clé primaire multiple_registration & purge aneti_2019"]
    E3 --> E4["[4/7] drush updatedb -y<br>Mise à niveau des tables SQL D8 ➔ D9"]
    E4 --> E5["[5/7] Thème Claro<br>Activation Claro & désinstallation Seven"]
    E5 --> E6["[6/7] drush cim -y<br>Import de la configuration synchronisée D9"]
    E6 --> E7["[7/7] Finalisation<br>Sortie maintenance, drush cr, drush status & uli"]
```

### Étape 1 : Vérification BDD & Nettoyage préalable
* Vérifie si des tables orphelines de migration D6/D7 (`migrate_map_*`) subsistent dans la base.
* Si oui, lance automatiquement [`scripts/1-cleanup_db_pre_upgrade.sh -y`](file:///Users/irtam/Desktop/Drupal/aneti-prod%203/scripts/1-cleanup_db_pre_upgrade.sh) pour libérer plus de **1 Go** de données volatiles (5M de lignes de recherche, caches, watchdog).

### Étape 2 : Neutralisation des timeouts SMTP et téléchargements distants
* Exécute une mise à jour directe en SQL pour forcer `smtp_on: false` et désactiver les alertes e-mail dans `config_log` (`log_destination.mail = 0`). Cela empêche tout timeout réseau sur le serveur inaccessible `192.168.1.10:25` lors des sauvegardes de configurations.
* Configure `locale.settings translation.use_source = local` pour éviter l'erreur `TypeError fgets()` liée aux téléchargements distants de fichiers de langue manquants.

### Étape 3 : Sécurisation de `multiple_registration`, nettoyage des orphelins et désenregistrement des modules
* Supprime préventivement en base les entités de configuration orphelines ou conflictuelles :
  * `search.page.content_exclude_` (référençant le plugin manquant `search_exclude_node_search`).
  * `field.field.simplenews_subscriber.simplenews_subscriber.subscriptions` et son `field.storage` (qui entraient en collision avec le champ natif de Simplenews 2.x).
  * Configurations obsolètes de Devel et blocs orphelins du thème `aneti_2019`.
* Désenregistre proprement via l'API Drupal les modules dont le code a été supprimé (`select2`, `conditional_fields`, `unique_entity_title`, `rules_role_email`, `taxonomy_term_depth`, `taxonomy_import`, `menu_export`, `search_exclude`, `devel`, `rest_log`, `node_action_log`, `aneti_tools`, `contact_message_rest`, `dbug`).
* Désinstalle via Drush les modules dépréciés du core (`color`, `quickedit`, `rdf`, `hal`, `tracker`).
* Applique directement la clé primaire `(uid, rid)` sur `multiple_registration` et enregistre le schéma 8204 dans le registre Drush. Les **318 075 comptes utilisateurs restent 100 % intacts** et la boucle infinie est neutralisée.

### Étape 4 : Mises à jour de schémas (`drush updatedb`) & Définitions d'entités
* Passe le site en mode maintenance pour garantir l'intégrité transactionnelle.
* Exécute l'ensemble des hooks de mise à jour de schéma BDD (`drush updatedb -y`).
* Purge automatiquement la définition de champ résiduelle `depth_level` sur l'entité `taxonomy_term` et met à jour la définition de l'entité `paragraph`.
* Vide et reconstruit le cache Drupal.

### Étape 5 : Thème d'administration Claro
* Active le thème officiel moderne **Claro**.
* Configure Claro comme thème d'administration principal.
* Désinstalle proprement l'ancien thème déprécié **Seven**.

### Étape 6 : Import de la configuration synchronisée (`drush cim`)
* Importe les fichiers YAML versionnés du dossier `config/sync/` dans la base de données.
* Prend en compte dynamiquement la séparation **Config Split** selon la variable `ENVIRONMENT` définie dans le fichier [`.env`](file:///Users/irtam/Desktop/Drupal/aneti-prod%203/.env).
* Reconstruit les caches d'entités et de routage.

### Étape 7 : Sortie de maintenance & Restitution
* Désactive le mode maintenance.
* Exécute un `drush cr` final.
* Affiche l'état de santé du site (`drush status`) avec confirmation du bootstrap.
* Génère et affiche un lien de connexion administrateur one-time (`drush uli`).

---

## 4. Gestion Multi-Environnement : Config Split & Fichier `.env`

Le projet intègre désormais une architecture stricte de gestion de configuration par environnement :

* **Fichier `.env` racine** :
  Permet de définir l'environnement d'exécution via la variable `ENVIRONMENT` :
  ```ini
  # dev : active le split 'dev' et les outils de développement (devel)
  # prod : environnement de production strict (aucun module de dev)
  ENVIRONMENT=dev
  ```
* **Fichier `.env.example`** :
  Fichier modèle versionné sous Git, configuré par défaut à `ENVIRONMENT=prod`.
* **Configuration Split (`config/split/dev/`)** :
  * Le paquet `drupal/devel:^5.1` est placé dans `require-dev` dans `composer.json`.
  * La configuration de `devel` (`devel.settings.yml`, `devel.toolbar.settings.yml`, `system.menu.devel.yml`) réside exclusivement dans `config/split/dev/`.
  * Le dossier `config/sync/` principal de production ne contient **aucune référence à `devel`**.
  * Dans `settings.php`, le split est activé dynamiquement :
    ```php
    $config['config_split.config_split.dev']['status'] = ($site_env === 'dev');
    ```

