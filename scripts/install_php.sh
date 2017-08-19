#!/bin/sh
# Installs php 5.6
set -x

export PHP_FOLDER=/opt/php/bin
DIRECTORY=/opt/php
REPO=https://github.com/dennisinteractive/php.git
BRANCH=php5_6

# Delete existing directory
if [ -e ${DIRECTORY} ]; then
  sudo rm -rf ${DIRECTORY}
fi

# Checkout repo
sudo git clone --branch ${BRANCH} ${REPO} ${DIRECTORY}
