#!/bin/sh
# Installs composer

DIRECTORY=/usr/local/bin/composer
COMPOSER=${DIRECTORY}/composer

cd ~
PATH="${PHP_FOLDER}/:$PATH" php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
PATH="${PHP_FOLDER}/:$PATH" php composer-setup.php

if [ -e "${COMPOSER}" ]; then
  sudo rm ${COMPOSER}
fi

if [ ! -e "${DIRECTORY}" ]; then
  sudo mkdir DIRECTORY
fi

sudo mv composer.phar ${COMPOSER}
sudo chmod +x ${COMPOSER}
