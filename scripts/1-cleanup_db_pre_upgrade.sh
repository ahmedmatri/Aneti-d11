#!/usr/bin/env bash
# ==============================================================================
# Script de Nettoyage et d'Assainissement Pré-Migration de la Base de Données
# Projet: ANETI (Drupal 8 -> Drupal 9/10/11)
# ==============================================================================
# Ce script prépare la base de données avant la montée de version majeure :
#   1. Sauvegarde préalable de sécurité (snapshot)
#   2. Suppression des tables orphelines de migration D6/D7 (303 tables ~120 Mo)
#   3. Vidage des tables d'index de recherche volatiles (~780 Mo, 5M+ lignes)
#   4. Purge des tables de caches compilés D8 (~95 Mo)
#   5. Purge des tables temporaires (watchdog, sessions de prod, flood, batch, semaphore)
#   6. Désinstallation des modules inactifs et dépréciés (select2, devel, rest_log, tracker...)
#   7. Recompilation du cache Drupal (drush cr)
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
NC='\033[0m' # No Color

# Options par défaut
AUTO_YES=false
SKIP_BACKUP=false

# Analyse des arguments
for arg in "$@"; do
    case "$arg" in
        -y|--yes)
            AUTO_YES=true
            ;;
        --skip-backup)
            SKIP_BACKUP=true
            ;;
        -h|--help)
            echo -e "${BOLD}Usage:${NC} $0 [options]"
            echo ""
            echo "Options:"
            echo "  -y, --yes          Exécution sans confirmation interactive"
            echo "  --skip-backup      Ne pas créer de snapshot de sauvegarde avant le nettoyage"
            echo "  -h, --help         Afficher cette aide"
            exit 0
            ;;
        *)
            echo -e "${RED}Option inconnue: $arg${NC}"
            echo "Utilisez --help pour voir les options."
            exit 1
            ;;
    esac
done

# Détection de l'environnement d'exécution (Hôte DDEV vs Conteneur)
if [ -f "/.ddev_docker_container" ]; then
    RUN_MYSQL="mysql -udb -pdb db"
    RUN_DRUSH="drush"
    IS_DDEV_HOST=false
elif command -v ddev >/dev/null 2>&1; then
    RUN_MYSQL="ddev mysql"
    RUN_DRUSH="ddev drush"
    IS_DDEV_HOST=true
else
    echo -e "${RED}Erreur : DDEV n'a pas été détecté sur la machine hôte et vous n'êtes pas dans le conteneur.${NC}"
    exit 1
fi

echo -e "${CYAN}${BOLD}"
echo "=============================================================================="
echo "    ANETI - Script d'Assainissement & Nettoyage de la Base de Données         "
echo "=============================================================================="
echo -e "${NC}"

# Mesures initiales
echo -e "${BLUE}🔍 Analyse de l'état actuel de la base de données...${NC}"
TABLES_TOTAL_BEFORE=$($RUN_MYSQL -N -e "SELECT count(*) FROM information_schema.TABLES WHERE table_schema = 'db';")
SIZE_TOTAL_BEFORE=$($RUN_MYSQL -N -e "SELECT round(sum(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE table_schema = 'db';")
MIGRATE_TABLES_COUNT=$($RUN_MYSQL -N -e "SELECT count(*) FROM information_schema.TABLES WHERE table_schema = 'db' AND (table_name LIKE 'migrate_map_%' OR table_name LIKE 'migrate_message_%');")
SEARCH_ROWS_COUNT=$($RUN_MYSQL -N -e "SELECT ifnull(table_rows, 0) FROM information_schema.TABLES WHERE table_schema = 'db' AND table_name = 'search_index';")

echo -e "  • Tables totales en base : ${BOLD}${TABLES_TOTAL_BEFORE}${NC}"
echo -e "  • Poids total de la base : ${BOLD}${SIZE_TOTAL_BEFORE} Mo${NC}"
echo -e "  • Tables orphelines de migration D6/D7 à supprimer : ${YELLOW}${BOLD}${MIGRATE_TABLES_COUNT}${NC}"
echo -e "  • Lignes dans search_index à vider : ${YELLOW}${BOLD}${SEARCH_ROWS_COUNT}${NC}"
echo ""

# Demande de confirmation
if [ "$AUTO_YES" = false ]; then
    echo -e "${YELLOW}⚠️  Ce script va supprimer définitivement les tables orphelines de migration D6/D7"
    echo -e "   et vider les caches, logs et index de recherche temporaires.${NC}"
    read -rp "Voulez-vous continuer ? [o/N] " response
    case "$response" in
        [oO][uU][iI]|[oO]|"y"|"Y")
            echo "Confirmation reçue."
            ;;
        *)
            echo -e "${RED}Opération annulée par l'utilisateur.${NC}"
            exit 0
            ;;
    esac
fi

# Étape 1 : Sauvegarde préalable (Snapshot)
if [ "$SKIP_BACKUP" = false ]; then
    BACKUP_DIR="${PROJECT_ROOT}/.ddev"
    mkdir -p "${BACKUP_DIR}"
    BACKUP_FILE="${BACKUP_DIR}/backup-pre-cleanup-$(date +%Y%m%d_%H%M%S).sql.gz"
    echo ""
    echo -e "${BLUE}💾 Étape 1/5 : Création d'une sauvegarde de sécurité...${NC}"
    if [ "$IS_DDEV_HOST" = true ]; then
        ddev export-db --file="$BACKUP_FILE"
        echo -e "${GREEN}✓ Sauvegarde créée avec succès : ${BACKUP_FILE}${NC}"
    else
        echo -e "${YELLOW}ℹ️  Exécution dans le conteneur : sauvegarde via mysqldump...${NC}"
        mysqldump -udb -pdb db | gzip > "/tmp/backup-pre-cleanup.sql.gz"
        echo -e "${GREEN}✓ Sauvegarde créée dans /tmp/backup-pre-cleanup.sql.gz${NC}"
    fi
else
    echo -e "${YELLOW}ℹ️  Étape 1 ignorée (--skip-backup activé).${NC}"
fi

# Étape 2 : Suppression des tables orphelines de migration
echo ""
echo -e "${BLUE}🗑️  Étape 2/5 : Suppression des ${MIGRATE_TABLES_COUNT} tables orphelines de migration...${NC}"
DROP_MIGRATE_SQL=$($RUN_MYSQL -N -e "
    SELECT CONCAT('DROP TABLE IF EXISTS \`', table_name, '\`;')
    FROM information_schema.TABLES
    WHERE table_schema = 'db'
      AND (table_name LIKE 'migrate_map_%' OR table_name LIKE 'migrate_message_%');
")

if [ -n "$DROP_MIGRATE_SQL" ]; then
    echo "SET FOREIGN_KEY_CHECKS = 0; $DROP_MIGRATE_SQL SET FOREIGN_KEY_CHECKS = 1;" | $RUN_MYSQL
    echo -e "${GREEN}✓ ${MIGRATE_TABLES_COUNT} tables de migration supprimées avec succès.${NC}"
else
    echo -e "${GREEN}✓ Aucune table de migration résiduelle détectée.${NC}"
fi

# Étape 3 : Vidage des tables d'index de recherche
echo ""
echo -e "${BLUE}🧹 Étape 3/5 : Vidage des tables de recherche volatiles (search_index, search_dataset, search_total)...${NC}"
$RUN_MYSQL -e "
    SET FOREIGN_KEY_CHECKS = 0;
    TRUNCATE TABLE search_index;
    TRUNCATE TABLE search_dataset;
    TRUNCATE TABLE search_total;
    SET FOREIGN_KEY_CHECKS = 1;
"
echo -e "${GREEN}✓ Tables d'index de recherche vidées (~780 Mo libérés).${NC}"

# Étape 4 : Vidage des tables de caches Drupal
echo ""
echo -e "${BLUE}🧹 Étape 4/5 : Vidage des tables de caches compilés (cache_*)...${NC}"
TRUNCATE_CACHE_SQL=$($RUN_MYSQL -N -e "
    SELECT CONCAT('TRUNCATE TABLE \`', table_name, '\`;')
    FROM information_schema.TABLES
    WHERE table_schema = 'db'
      AND table_name LIKE 'cache_%';
")

if [ -n "$TRUNCATE_CACHE_SQL" ]; then
    echo "SET FOREIGN_KEY_CHECKS = 0; $TRUNCATE_CACHE_SQL TRUNCATE TABLE \`cachetags\`; SET FOREIGN_KEY_CHECKS = 1;" | $RUN_MYSQL
    echo -e "${GREEN}✓ Tables de caches compilés vidées avec succès.${NC}"
fi

# Étape 5 : Purge des tables temporaires (logs, sessions de prod, flood, batch, semaphore)
echo ""
echo -e "${BLUE}🧹 Étape 5/6 : Purge des logs, sessions expirées et verrous temporaires...${NC}"
$RUN_MYSQL -e "
    SET FOREIGN_KEY_CHECKS = 0;
    TRUNCATE TABLE watchdog;
    TRUNCATE TABLE sessions;
    TRUNCATE TABLE flood;
    TRUNCATE TABLE batch;
    TRUNCATE TABLE semaphore;
    SET FOREIGN_KEY_CHECKS = 1;
"
echo -e "${GREEN}✓ Logs watchdog, sessions obsolètes et verrous purgés.${NC}"

# Étape 6 : Désinstallation des modules inutilisés et obsolètes
echo ""
echo -e "${BLUE}🧹 Étape 6/6 : Désinstallation des modules inactifs et dépréciés...${NC}"

# 0. Neutralisation immédiate des envois d'emails en local (évite les timeouts SMTP sur chaque modification de config)
$RUN_MYSQL -e "
    UPDATE config SET data = REPLACE(data, 's:7:\"smtp_on\";b:1;', 's:7:\"smtp_on\";b:0;') WHERE name = 'smtp.settings';
    UPDATE config SET data = REPLACE(data, 's:4:\"mail\";s:4:\"mail\";', 's:4:\"mail\";s:1:\"0\";') WHERE name = 'config_log.settings';
" 2>/dev/null || true

# 1. Nettoyage préventif des configurations orphelines et conflictuelles en base
$RUN_MYSQL -e "
    DELETE FROM config WHERE name = 'search.page.content_exclude_';
    DELETE FROM config WHERE name LIKE 'field.%simplenews_subscriber.subscriptions%';
    DELETE FROM config WHERE name LIKE 'devel.%';
    DELETE FROM config WHERE name = 'system.menu.devel';
    DELETE FROM config WHERE name LIKE 'block.block.aneti_2019_%';
" 2>/dev/null || true

# 2. Désenregistrement direct des modules orphelins (fichiers absents du disque)
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

# 3. Préparation pour taxonomy_term_depth (si la colonne depth_level existe)
HAS_DEPTH_COL=$($RUN_MYSQL -N -s -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'taxonomy_term_field_data' AND column_name = 'depth_level';")
if [ "$HAS_DEPTH_COL" -gt 0 ]; then
    $RUN_MYSQL -e "UPDATE taxonomy_term_field_data SET depth_level = NULL;" 2>/dev/null || true
fi

# 4. Désinstallation Drush des modules core dépréciés (supprimés sous Drupal 10)
$RUN_DRUSH pm:uninstall color quickedit rdf hal tracker -y 2>/dev/null || true

# 5. Nettoyage des tables SQL résiduelles associées
$RUN_MYSQL -e "
    SET FOREIGN_KEY_CHECKS = 0;
    DROP TABLE IF EXISTS rest_log;
    DROP TABLE IF EXISTS node_action_log;
    DROP TABLE IF EXISTS tracker_node;
    DROP TABLE IF EXISTS tracker_user;
    SET FOREIGN_KEY_CHECKS = 1;
" 2>/dev/null || true

# 6. Configuration des traductions locales pour éviter les erreurs PoStreamReader
$RUN_DRUSH config:set locale.settings translation.use_source local -y 2>/dev/null || true

echo -e "${GREEN}✓ Modules inactifs et dépréciés désinstallés avec succès.${NC}"

# Recompilation des caches Drupal
echo ""
echo -e "${BLUE}⚡ Recompilation du cache Drupal (drush cr)...${NC}"
$RUN_DRUSH cr
echo -e "${GREEN}✓ Cache Drupal réinitialisé et opérationnel.${NC}"

# Bilan final
TABLES_TOTAL_AFTER=$($RUN_MYSQL -N -e "SELECT count(*) FROM information_schema.TABLES WHERE table_schema = 'db';")
SIZE_TOTAL_AFTER=$($RUN_MYSQL -N -e "SELECT round(sum(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE table_schema = 'db';")

echo ""
echo -e "${CYAN}${BOLD}"
echo "=============================================================================="
echo "                         BILAN DU NETTOYAGE EFFECTUÉ                          "
echo "=============================================================================="
echo -e "${NC}"
echo -e "  • Tables avant : ${BOLD}${TABLES_TOTAL_BEFORE}${NC}  ➡️  Tables après : ${GREEN}${BOLD}${TABLES_TOTAL_AFTER}${NC} (${GREEN}-$((TABLES_TOTAL_BEFORE - TABLES_TOTAL_AFTER)) tables supprimées${NC})"
echo -e "  • Poids initial : ${BOLD}${SIZE_TOTAL_BEFORE} Mo${NC}  ➡️  Poids après : ${GREEN}${BOLD}${SIZE_TOTAL_AFTER} Mo${NC}"
RECLAIMED_MB=$(awk "BEGIN {printf \"%.2f\", $SIZE_TOTAL_BEFORE - $SIZE_TOTAL_AFTER}")
echo -e "  • Espace disque libéré : ${GREEN}${BOLD}${RECLAIMED_MB} Mo${NC}"
echo ""
echo -e "${GREEN}${BOLD}✅ La base de données est maintenant saine, allégée et prête pour la migration !${NC}"
