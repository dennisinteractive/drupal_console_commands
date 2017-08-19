#!/bin/sh
# Installs Drupal Console extend
# Extend is what provides the ability to run console commands globally
set -x

composer create-project \
drupal/console-extend ~/.console/extend \
--no-interaction

# Remove example
cd ~/.console/extend;
composer remove drupal/console-extend-example
