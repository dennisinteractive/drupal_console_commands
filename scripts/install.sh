#!/bin/sh
# Performs an installation of Drupal console commands
set -x

BIN_FOLDER="/usr/local/bin"
BIN_FILE="${BIN_FOLDER}/drupal"

# Delete existing bin file
if [ -e "${BIN_FILE}" ]; then
  sudo rm ${BIN_FILE}
fi

# Download latest bin
curl https://drupalconsole.com/installer -L -o /tmp/drupal.phar
sudo mv /tmp/drupal.phar ${BIN_FILE}
chmod +x ${BIN_FILE}

# Setup drupal
drupal init --override
drupal settings:set environment dev

# Install console extend
# rm -rf ~/.composer/extend;
composer create-project drupal/console-extend:dev-master#efe180b00827fc1288c2244eee1db3b02c574fe1 ~/.console/extend

cd ~/.console/extend
# Temporary fix to avoid conflicts between packages
composer update

# Install custom commands
composer require dennisdigital/drupal_console_commands:dev-drupal_extend --update-no-dev

# Copy chain commands
cp vendor/dennisdigital/drupal_console_commands/chain/*.yml ../chain
