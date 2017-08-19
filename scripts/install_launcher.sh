#!/bin/sh
# Installs Drupal Console launcher
set -x

DRUPAL_CONSOLE=/usr/local/bin/drupal

# Delete existing file
if [ -e "${DRUPAL_CONSOLE}" ]; then
  sudo rm ${DRUPAL_CONSOLE}
fi

PATH="${PHP_FOLDER}/:$PATH" php -r "readfile('https://drupalconsole.com/installer');" > ~/drupal.phar
sudo chmod +x ~/drupal.phar
sudo mv ~/drupal.phar ${DRUPAL_CONSOLE}
