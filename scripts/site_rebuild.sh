#!/bin/sh
# Alternative way to using chains

SITENAME=$1;

drupal site:compose ${SITENAME} && \
drupal site:npm --placeholder="name:${SITENAME}" && \
drupal site:grunt --placeholder="name:${SITENAME}" && \
drupal site:settings:db ${SITENAME} && \
drupal site:phpunit:setup ${SITENAME} && \
drupal site:behat:setup ${SITENAME} && \
drupal site:settings:memcache ${SITENAME} && \
drupal site:db:import ${SITENAME} && \
cd /vagrant/repos/${SITENAME}/web && drush updb -y && \
cd /vagrant/repos/${SITENAME}/web && drush cr
