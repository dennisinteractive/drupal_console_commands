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

drupal init --override

# Install console extend
# rm -rf ~/.composer/extend;
composer create-project drupal/console-extend:dev-master#227eb6e1d0d8d2abee09dde4e9a1044f301c93e1 ~/.console/extend

# Install custom commands
cd ~/.console/extend; composer require dennisdigital/drupal_console_commands:dev-drupal_extend --update-no-dev

composer update


