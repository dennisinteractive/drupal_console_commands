#!/bin/sh
# Performs an installation of Drupal console commands on Centos6
# Install Requirements
# - php5.6
# - composer
# - Drupal console commands

# Install php5_6
PHP_FOLDER="/opt/php/bin"

# @todo check the current php version and only do these if needed
sudo curl -L https://github.com/dennisinteractive/php/raw/php5_6/php > /tmp/php
if [ -e "/opt/php" ]; then
  sudo rm -rf /opt/php
  sudo mkdir /opt/php
fi
sudo mkdir ${PHP_FOLDER}
sudo mv /tmp/php ${PHP_FOLDER}

# Install composer
COMPOSER="/usr/local/bin/composer/composer"

# @todo check if there is already composer and only do these if needed
cd ~
PATH="${PHP_FOLDER}/:$PATH" php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
PATH="${PHP_FOLDER}/:$PATH" php composer-setup.php
if [ -e "${COMPOSER}" ]; then
  sudo rm ${COMPOSER}
  sudo mkdir /usr/local/bin/composer
fi
sudo mv composer.phar ${COMPOSER}

# These are more or less the same as install.sh

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
# @todo escape PHP_FOLDER
sed -i 's/\#\!\/usr\/bin\/env\sphp/\#\!\/opt\/php\/bin\/php/g' ~/.console/launcher/bin/drupal
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
