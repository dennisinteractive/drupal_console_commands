#!/bin/sh
# Alternative way to using chains

#if [ "$1" = "" ]
#  then
#    echo
#    echo "Syntax: site_build [site-name] [branch]"
#    echo
#    echo "You need to specify a site name (without the environment .dev)"
#    drupal site:debug
#    exit
#fi

SITENAME=$1;

if [ "$2" = "" ]
  then
    drupal site:checkout ${SITENAME}
  else
    drupal site:checkout ${SITENAME} -B $2
fi

drupal site:compose ${SITENAME} && \
drupal site:npm --placeholder"name:${SITENAME}" && \
drupal site:grunt --placeholder"name:${SITENAME}" && \
drupal site:settings:db ${SITENAME} && \
drupal site:phpunit:setup ${SITENAME} && \
drupal site:behat:setup ${SITENAME} && \
drupal site:settings:memcache ${SITENAME} && \
drupal site:db:import ${SITENAME} && \
cd /vagrant/repos/%{{name}}/web; drush updb -y
cd /vagrant/repos/%{{name}}/web; drush cr;
