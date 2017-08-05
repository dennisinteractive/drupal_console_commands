#!/bin/sh
# Performs an installation of Drupal console commands

# Checkout scripts
REPO="https://github.com/dennisinteractive/drupal_console_commands.git"
BRANCH="centos6"
git clone --branch ${BRANCH} ${REPO} /tmp
cd /tmp

PHP_FOLDER=$(which php)

# Install composer
sh install_composer.sh

# Install drupal console launcher
sh install_launcher.sh

# Setup Drupal console
drupal -n --override init
drupal settings:set environment dev

# Install console extend
sh install_extend.sh

# Install custom commands
sh install_custom_commands.sh
