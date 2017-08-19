#!/bin/sh
# Installs Drupal Console launcher
set -x

DRUPAL_CONSOLE=/usr/local/bin/drupal

# Delete existing file
if [ -e "${DRUPAL_CONSOLE}" ]; then
  sudo rm ${DRUPAL_CONSOLE}
fi

PATH="${PHP_FOLDER}/:$PATH" sudo php -r "readfile('https://drupalconsole.com/installer');" > ${DRUPAL_CONSOLE}
sudo chmod +x ${DRUPAL_CONSOLE}

