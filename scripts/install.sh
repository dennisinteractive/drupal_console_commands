#!/bin/sh
# Performs an installation of Drupal console commands
set +x

# Checkout scripts
REPO="https://github.com/dennisinteractive/drupal_console_commands.git"
BRANCH="distro_build_command"
sudo rm -rf /tmp/drupal_console_commands
git clone --branch ${BRANCH} ${REPO} /tmp/drupal_console_commands
cd /tmp/drupal_console_commands/scripts

PHP_FOLDER=$(which php)

# Install composer
# sh install_composer.sh

# Install drupal console launcher
sh install_launcher.sh

# Setup Drupal console
drupal -n --override init
drupal settings:set environment dev

# Install console extend plugin
sh install_extend.sh

# Install custom commands
sh install_custom_commands.sh

rm -rf /tmp/drupal_console_commands
