#!/bin/sh
# Installs php 5.6

DIRECTORY="/opt/php"
PHP_FOLDER="/opt/php/bin"
REPO="https://github.com/dennisinteractive/php.git"
BRANCH="php5_6"

# Delete existing directory
if [ -e ${DIRECTORY} ]; then
  sudo rm -rf ${DIRECTORY}
fi

# Checkout repo
sudo git clone --branch ${BRANCH} ${REPO} ${DIRECTORY}
