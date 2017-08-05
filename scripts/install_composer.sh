#!/bin/sh
# Installs composer

# Check if there is already composer and only do these if needed
COMPOSER=$(which composer)

if [ -z {$COMPOSER} ]; then

    DIRECTORY="/usr/local/bin/composer"
    COMPOSER="${DIRECTORY}/composer"

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
else
    echo "Composer is already installed on ${COMPOSER}"
fi
