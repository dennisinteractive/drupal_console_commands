#!/bin/sh
# Installs Drupal Console custom commands
# These are our custom commands used to build sites

DIRECTORY="~/.console/extend"
PACKAGE="dennisdigital/drupal_console_commands"
BRANCH="centos6"

# Build package
cd ${DIRECTORY}
PATH="${PHP_FOLDER}/:$PATH" composer require ${PACKAGE}:${BRANCH}-dev

# Copy chain commands
cp vendor/${PACKAGE}/chain/*.yml ${DIRECTORY}/../chain

echo All done!
echo Now put some yml files into ~/.console/sites and you are ready to go
