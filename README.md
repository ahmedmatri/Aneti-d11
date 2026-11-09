# ANETI 2019

## Manual Setup
* Create a database
* git clone git@gitlab.com:aneti/aneti-2019.git
* cd aneti-2019
* cp web/sites/default/settings.php.example web/sites/default/settings.php
* change database config in copier settings.php
* Use the dump file located in dump/latest.sql.gz
* cd web && ../vendor/bin/drupal server # Run a local webserver


## Dev workflow
* create a new branch from master
* add your code
* export config
* commit push and add a Merge request
