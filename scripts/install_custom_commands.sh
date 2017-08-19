#!/bin/sh
# Installs Drupal Console custom commands
# These are our custom commands used to build sites
set -x

DIRECTORY=~/.console/extend
PACKAGE=dennisdigital/drupal_console_commands
BRANCH=extend_properly

# Build package
cd ${DIRECTORY}
PATH="${PHP_FOLDER}/:$PATH" composer require ${PACKAGE}:${BRANCH}-dev

# Copy chain commands
cp vendor/${PACKAGE}/chain/*.yml ~/.console/chain

drupal site:debug
