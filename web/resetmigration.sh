#!/bin/sh

echo "Reset migration..."
drush pm-uninstall aneti_migrate && \
drush en -y aneti_migrate && \
drush migrate-upgrade --configure-only --legacy-db-key=upgrade && \
echo "Done !"