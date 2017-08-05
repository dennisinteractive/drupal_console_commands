#!/bin/sh
# Performs an installation of Drupal console commands on Centos6

# Install our custom php
sh install_php.sh

# Install composer
sh install_composer.sh

# Install drupal console launcher
sh install_launcher.sh

# Use our custom php for drupal console
# @todo use ${PHP_FOLDER}
sed -i 's/\#\!\/usr\/bin\/env\sphp/\#\!\/opt\/php\/bin\/php/g' ~/.console/launcher/bin/drupal

# Setup Drupal console
drupal -n --override init
drupal settings:set environment dev

# Install console extend
sh install_extend.sh

# Install custom commands
sh install_custom_commands.sh
