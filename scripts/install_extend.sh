#!/bin/sh
# Installs Drupal Console extend
# Extend is what provides the ability to run console commands globally

DIRECTORY="~/.console/extend"
PACKAGE="drupal/console-extend"
REPO="git@github.com:dennisinteractive/drupal-console-extend.git"
BRANCH="fix_env_detection"

# Delete existing directory
if [ -e ${DIRECTORY} ]; then
  rm -fr ${DIRECTORY}
fi

# Build package
PATH="${PHP_FOLDER}/:$PATH" \
composer create-project \
--repository='{"type": "vcs", "url": ${REPO}, "vendor-alias": "drupal", "no-api": true}' \
${PACKAGE}:${BRANCH}-dev ${DIRECTORY}
