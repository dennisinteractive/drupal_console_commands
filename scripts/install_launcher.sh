#!/bin/sh
# Installs Drupal Console launcher
# Launcher is an alternative to running the drupal bin compiled and distributed

DRUPAL_CONSOLE=/usr/local/bin/drupal
DIRECTORY=~/.console/launcher
PACKAGE=drupal/console-launcher
BRANCH=fix_env_detection

# Delete existing file
if [ -e "${DRUPAL_CONSOLE}" ]; then
  sudo rm ${DRUPAL_CONSOLE}
fi

# Delete existing directory
if [ -e ${DIRECTORY} ]; then
  rm -fr ${DIRECTORY}
fi

# Build package
PATH="${PHP_FOLDER}/:$PATH" \
composer create-project \
--repository='{
"type": "vcs",
"url": "git@github.com:dennisinteractive/drupal-console-launcher.git",
"vendor-alias": "drupal",
"no-api": true}' \
${PACKAGE}:${BRANCH}-dev ${DIRECTORY}

# Make executable
chmod +x ${DIRECTORY}/bin/drupal

# Create synlink
sudo ln -s ${DIRECTORY}/bin/drupal ${DRUPAL_CONSOLE}
