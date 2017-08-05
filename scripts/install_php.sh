#!/bin/sh
# Installs php 5.6

DIRECTORY="/opt/php"
PHP_FOLDER="/opt/php/bin"
REPO="git@github.com:dennisinteractive/php.git"
BRANCH="php5_6"

# Delete existing directory
if [ -e ${DIRECTORY} ]; then
  rm -fr ${DIRECTORY}
fi

# Checkout repo
git clone --branch ${BRANCH} ${REPO} ${DIRECTORY}
