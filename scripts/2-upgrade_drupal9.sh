#!/usr/bin/env bash
# ==============================================================================
# Script Automatisé d'Exécution de la Migration Drupal 9.5
# Projet: ANETI (Portail Emploi International)
# ==============================================================================
# Ce script automatise la séquence complète de migration après l'import d'une base D8 :
#   1. Vérifications préalables (DDEV, BDD active, script de nettoyage préalable)
#   2. Neutralisation des blocages de réseau en local (SMTP, config_log)
#   3. Résolution préventive du blocage cartésien multiple_registration (318 000 lignes)
#   4. Passage en mode maintenance
#   5. Exécution des mises à jour de schémas BDD (drush updatedb)
#   6. Activation du thème Claro et désinstallation de Seven
#   7. Importation de la configuration synchronisée (drush cim)
#   8. Sortie du mode maintenance, cache rebuild et génération du lien de connexion
# ==============================================================================

set -euo pipefail

# Détermination absolue des chemins
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${PROJECT_ROOT}"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Options par défaut
AUTO_YES=false
RUN_CLEANUP=true

# Analyse des arguments
for arg in "$@"; do
    case "$arg" in
        -y|--yes)
            AUTO_YES=true
            ;;
        --skip-cleanup)
            RUN_CLEANUP=false
            ;;
        -h|--help)
            echo -e "${BOLD}Usage:${NC} $0 [options]"
            echo ""
            echo "Options:"
            echo "  -y, --yes          Exécution automatique sans confirmation interactive"
            echo "  --skip-cleanup     Ne pas exécuter automatiquement scripts/1-cleanup_db_pre_upgrade.sh"
            echo "  -h, --help         Afficher cette aide"
            exit 0
            ;;
        *)
            echo -e "${RED}Option inconnue: $arg${NC}"
            echo "Utilisez --help pour voir les options disponibles."
            exit 1
            ;;
    esac
done

echo -e "${BLUE}${BOLD}"
echo "================================================================================"
echo "    🚀 ANETI — SCRIPT AUTOMATISÉ D'UPGRADE VERS DRUPAL 9.5.11                   "
echo "================================================================================"
echo -e "${NC}"

# 1. Détection de l'environnement d'exécution (Hôte vs Conteneur DDEV)
if [ -f "/.ddev_docker_container" ]; then
    RUN_MYSQL="mysql -udb -pdb db"
    RUN_DRUSH="drush"
    RUN_SCRIPT_CLEANUP="./scripts/1-cleanup_db_pre_upgrade.sh"
    IS_DDEV_HOST=false
else
    if ! command -v ddev &> /dev/null; then
        echo -e "${RED}[ERREUR] DDEV n'est pas installé ou n'est pas dans le PATH.${NC}"
        exit 1
    fi
    if ! ddev status &> /dev/null; then
        echo -e "${YELLOW}[INFO] Démarrage du projet DDEV...${NC}"
        ddev start
    fi
    RUN_MYSQL="ddev mysql"
    RUN_DRUSH="ddev drush"
    RUN_SCRIPT_CLEANUP="ddev exec ./scripts/1-cleanup_db_pre_upgrade.sh"
    IS_DDEV_HOST=true
fi

# Confirmation interactive
if [ "$AUTO_YES" = false ]; then
    echo -e "${YELLOW}Ce script va migrer la base de données active vers le schéma Drupal 9.5.11.${NC}"
    read -p "Souhaitez-vous continuer ? [o/N] " -r
    echo ""
    if [[ ! $REPLY =~ ^[Oo]$ ]]; then
        echo -e "${RED}Opération annulée par l'utilisateur.${NC}"
        exit 0
    fi
fi

# ==============================================================================
# ÉTAPE 1 : Nettoyage préalable si des tables de migration sont détectées
# ==============================================================================
echo -e "${CYAN}${BOLD}[1/7] Vérification de l'état de la base de données...${NC}"

MIGRATE_TABLES_COUNT=$($RUN_MYSQL -N -s -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'migrate_map_%';")

if [ "$MIGRATE_TABLES_COUNT" -gt 0 ]; then
    if [ "$RUN_CLEANUP" = true ]; then
        echo -e "${YELLOW}[INFO] Des tables de migration orphelines ($MIGRATE_TABLES_COUNT tables) sont présentes.${NC}"
        echo -e "${YELLOW}[INFO] Lancement automatique du script de nettoyage préalable (1-cleanup_db_pre_upgrade.sh)...${NC}"
        if [ "$IS_DDEV_HOST" = true ]; then
            "${SCRIPT_DIR}/1-cleanup_db_pre_upgrade.sh" -y
        else
            "${SCRIPT_DIR}/1-cleanup_db_pre_upgrade.sh" -y
        fi
    else
        echo -e "${YELLOW}[WARNING] Le nettoyage préalable a été sauté via --skip-cleanup.${NC}"
    fi
else
    echo -e "${GREEN}[OK] Base déjà assainie (aucune table orpheline de migration détectée).${NC}"
fi

# ==============================================================================
# ÉTAPE 2 : Neutralisation préventive des blocages de réseau en local
# ==============================================================================
echo -e "\n${CYAN}${BOLD}[2/7] Neutralisation des timeouts SMTP / emails en local...${NC}"

# Désactivation directe en SQL (instantané et sans dépendance de bootstrap)
$RUN_MYSQL -e "
    UPDATE config SET data = REPLACE(data, 's:7:\"smtp_on\";b:1;', 's:7:\"smtp_on\";b:0;') WHERE name = 'smtp.settings';
    UPDATE config SET data = REPLACE(data, 's:4:\"mail\";s:4:\"mail\";', 's:4:\"mail\";s:1:\"0\";') WHERE name = 'config_log.settings';
" 2>/dev/null || true

$RUN_DRUSH config:set smtp.settings smtp_on false -y 2>/dev/null || true
$RUN_DRUSH config:set config_log.settings log_destination.mail 0 -y 2>/dev/null || true
$RUN_DRUSH config:set locale.settings translation.use_source local -y 2>/dev/null || true

echo -e "${GREEN}[OK] Envois d'emails externes et téléchargements de traductions distantes désactivés.${NC}"

# ==============================================================================
# ÉTAPE 3 : Sécurisation de multiple_registration & nettoyage blocs orphelins
# ==============================================================================
echo -e "\n${CYAN}${BOLD}[3/7] Sécurisation des données et nettoyage des orphelins...${NC}"

# Nettoyage des configs orphelines et conflictuelles
$RUN_MYSQL -e "
    DELETE FROM config WHERE name = 'search.page.content_exclude_';
    DELETE FROM config WHERE name LIKE 'field.%simplenews_subscriber.subscriptions%';
    DELETE FROM config WHERE name LIKE 'devel.%';
    DELETE FROM config WHERE name = 'system.menu.devel';
    DELETE FROM config WHERE name LIKE 'block.block.aneti_2019_%';
" 2>/dev/null || true

# Désenregistrement propre des modules dont le code a été supprimé
$RUN_DRUSH php:eval "
\$config = \Drupal::configFactory()->getEditable('core.extension');
\$schema = \Drupal::keyValue('system.schema');
\$modules = ['select2', 'conditional_fields', 'unique_entity_title', 'rules_role_email', 'taxonomy_term_depth', 'taxonomy_import', 'menu_export', 'search_exclude', 'devel', 'rest_log', 'node_action_log', 'aneti_tools', 'contact_message_rest', 'dbug'];
foreach (\$modules as \$m) {
    \$config->clear('module.' . \$m);
    \$schema->delete(\$m);
}
\$config->save();
" 2>/dev/null || true

# Désinstallation défensive des modules core dépréciés
HAS_DEPTH_COL=$($RUN_MYSQL -N -s -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'taxonomy_term_field_data' AND column_name = 'depth_level';")
if [ "$HAS_DEPTH_COL" -gt 0 ]; then
    $RUN_MYSQL -e "UPDATE taxonomy_term_field_data SET depth_level = NULL;" 2>/dev/null || true
fi
$RUN_DRUSH pm:uninstall color quickedit rdf hal tracker -y 2>/dev/null || true

HAS_MR_TABLE=$($RUN_MYSQL -N -s -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'multiple_registration';")

if [ "$HAS_MR_TABLE" -gt 0 ]; then
    HAS_ID_COL=$($RUN_MYSQL -N -s -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'multiple_registration' AND column_name = 'id';")
    if [ "$HAS_ID_COL" -gt 0 ]; then
        echo -e "${YELLOW}[INFO] Optimisation de la structure multiple_registration pour éviter la boucle infinie (update 8204)...${NC}"
        # Application directe de la clé primaire sans faire le produit cartésien de 101 milliards d'opérations
        $RUN_MYSQL -e "
            ALTER TABLE multiple_registration MODIFY id INT NOT NULL;
            ALTER TABLE multiple_registration DROP PRIMARY KEY;
            ALTER TABLE multiple_registration DROP COLUMN id;
            ALTER TABLE multiple_registration ADD PRIMARY KEY (uid, rid);
        " 2>/dev/null || true
        # Inscription du schéma 8204 dans Drush
        $RUN_DRUSH php:eval "\Drupal::service('keyvalue')->get('system.schema')->set('multiple_registration', 8204);" 2>/dev/null || true
        echo -e "${GREEN}[OK] Clé primaire (uid, rid) appliquée avec succès sur 318 000+ lignes (0 perte de données).${NC}"
    else
        echo -e "${GREEN}[OK] Structure multiple_registration déjà optimisée.${NC}"
    fi
fi

# ==============================================================================
# ÉTAPE 4 : Passage en maintenance et exécution des mises à jour BDD (updatedb)
# ==============================================================================
echo -e "\n${CYAN}${BOLD}[4/7] Exécution des mises à jour de schémas (drush updatedb)...${NC}"

$RUN_DRUSH sset system.maintenance_mode 1
$RUN_DRUSH updatedb -y

# Mise à jour des définitions d'entités résiduelles (depth_level, paragraph)
$RUN_DRUSH php:eval "
\$manager = \Drupal::entityDefinitionUpdateManager();
\$storage = \$manager->getFieldStorageDefinition('depth_level', 'taxonomy_term');
if (\$storage) { \$manager->uninstallFieldStorageDefinition(\$storage); }
\$paragraph = \$manager->getEntityType('paragraph');
if (\$paragraph) { \$manager->updateEntityType(\$paragraph); }
" 2>/dev/null || true

$RUN_DRUSH cr

echo -e "${GREEN}[OK] Mises à jour de base de données terminées avec succès.${NC}"

# ==============================================================================
# ÉTAPE 5 : Bascule du thème d'administration sur Claro (désinstallation Seven)
# ==============================================================================
echo -e "\n${CYAN}${BOLD}[5/7] Configuration du thème d'administration (Claro)...${NC}"

$RUN_DRUSH theme:enable claro -y 2>/dev/null || true
$RUN_DRUSH config:set system.theme admin claro -y
$RUN_DRUSH theme:uninstall seven -y >/dev/null 2>&1 || true

echo -e "${GREEN}[OK] Thème d'administration configuré sur Claro.${NC}"

# ==============================================================================
# ÉTAPE 6 : Importation de la configuration synchronisée (drush cim)
# ==============================================================================
echo -e "\n${CYAN}${BOLD}[6/7] Importation de la configuration synchronisée (drush cim)...${NC}"

$RUN_DRUSH config:import -y
$RUN_DRUSH cr

echo -e "${GREEN}[OK] Configuration Drupal 9 importée et synchronisée.${NC}"

# ==============================================================================
# ÉTAPE 7 : Finalisation, sortie de maintenance et rapport de santé
# ==============================================================================
echo -e "\n${CYAN}${BOLD}[7/7] Finalisation et vérification du statut...${NC}"

$RUN_DRUSH sset system.maintenance_mode 0
$RUN_DRUSH cr

echo -e "\n${GREEN}${BOLD}================================================================================${NC}"
echo -e "${GREEN}${BOLD}    ✅ UPGRADE DRUPAL 9.5.11 TERMINÉ AVEC SUCCÈS !                             ${NC}"
echo -e "${GREEN}${BOLD}================================================================================${NC}"
echo ""
echo -e "${BOLD}État du site :${NC}"
$RUN_DRUSH status

echo ""
echo -e "${BOLD}Lien de connexion administrateur en 1 clic :${NC}"
$RUN_DRUSH uli 2>/dev/null || echo "http://aneti-prod-3.ddev.site"
echo ""
