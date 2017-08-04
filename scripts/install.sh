#!/bin/sh
# Performs an installation of Drupal console commands

# Install drupal console
DRUPAL_CONSOLE="/usr/local/bin/drupal"

# Delete existing bin file
if [ -e "${DRUPAL_CONSOLE}" ]; then
  sudo rm ${DRUPAL_CONSOLE}
fi

# Install launcher
BRANCH=fix_env_detection

if [ -e ~/.console/launcher ]; then
  rm -fr ~/.console/launcher
fi
PATH="${PHP_FOLDER}/:$PATH" composer create-project --repository='{"type": "vcs", "url": "git@github.com:dennisinteractive/drupal-console-launcher.git", "vendor-alias": "drupal", "no-api": true}' drupal/console-launcher:${BRANCH}-dev ~/.console/launcher
sudo ln -s ~/.console/launcher/bin/drupal ${DRUPAL_CONSOLE}
chmod +x ${DRUPAL_CONSOLE}

# Setup Drupal console
drupal -n --override init
drupal settings:set environment dev

# Install console extend
BRANCH=fix_env_detection

if [ -e ~/.console/extend ]; then
  rm -fr ~/.console/extend
fi
PATH="${PHP_FOLDER}/:$PATH" composer create-project --repository='{"type": "vcs", "url": "git@github.com:dennisinteractive/drupal-console-extend.git", "vendor-alias": "drupal", "no-api": true}' drupal/console-extend:${BRANCH}-dev ~/.console/extend

# Install custom commands
BRANCH=module_enable_disable

cd ~/.console/extend
PATH="${PHP_FOLDER}/:$PATH" composer require dennisdigital/drupal_console_commands:${BRANCH}-dev

# Copy chain commands
cp vendor/dennisdigital/drupal_console_commands/chain/*.yml ../chain

echo All done
echo Now put some yml files onto ~/.console/sites and you are ready to go
