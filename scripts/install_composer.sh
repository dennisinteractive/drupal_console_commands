#!/bin/sh
# Installs composer

DIRECTORY=/usr/local/bin/composer
COMPOSER=${DIRECTORY}/composer

cd ~
PATH="${PHP_FOLDER}/:$PATH" php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
PATH="${PHP_FOLDER}/:$PATH" php composer-setup.php

if [ -e "${DIRECTORY}" ]; then
  sudo rm -rf ${COMPOSER}
fi
sudo mkdir ${DIRECTORY}

sudo chmod +x composer.phar
sudo mv composer.phar ${COMPOSER}
