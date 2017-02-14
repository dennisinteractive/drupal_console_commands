#!/bin/sh
# Alternative way to using chains. The benefit of using a bash script is that the intactive mode of the commands work.

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
cd /vagrant/repos/${SITENAME}/web && drush cim -y && \
cd /vagrant/repos/${SITENAME}/web && drush cr
