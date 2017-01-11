#!/bin/sh
# Performs an installation of Drupal console commands

BIN_FOLDER="/usr/local/bin"
BIN_FILE="${BIN_FOLDER}/drupal"

# Delete existing bin file
if [ -e "${BIN_FILE}" ]; then
  sudo rm ${BIN_FILE}
fi

# Install launcher
if [ -e ~/.console/launcher ]; then
  rm -fr ~/.console/launcher
fi
#composer create-project drupal/console-launcher:dev-master#88a32775ac0e892859f7da7dfb00901986e399b8 ~/.console/launcher
# Using our fork of launcher with nested chain fix.
composer create-project --repository='{"type": "vcs", "url": "git@github.com:dennisinteractive/drupal-console-launcher.git", "vendor-alias": "drupal", "no-api": true}' drupal/console-launcher:dev-dennis-master#8cdb59c82915bf26a2f33d9f26628abb55b724b4 ~/.console/launcher
sudo ln -s ~/.console/launcher/bin/drupal /usr/local/bin/drupal
chmod +x ${BIN_FILE}

# Setup drupal
drupal -n --override init
drupal settings:set environment dev

# Install console extend
if [ -e ~/.console/extend ]; then
  rm -fr ~/.console/extend
fi
#composer create-project drupal/console-extend:dev-master#efe180b00827fc1288c2244eee1db3b02c574fe1 ~/.console/extend
# Using our fork of extend with nested chain fix.
composer create-project --repository='{"type": "vcs", "url": "git@github.com:dennisinteractive/drupal-console-extend.git", "vendor-alias": "drupal", "no-api": true}' drupal/console-extend:dev-dennis-master#622eee8ef886f2ddcfd727dac0adea9d5f338c3b ~/.console/extend

# Install custom commands
cd ~/.console/extend
composer require dennisdigital/drupal_console_commands:dev-master

# Copy chain commands
cp vendor/dennisdigital/drupal_console_commands/chain/*.yml ../chain
