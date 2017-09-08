#!/bin/sh
# Alternative way to using chains

SITENAME=$1;

if [ "$2" = "" ]
  then
    CMD="drupal site:checkout ${SITENAME}"
  else
    CMD="drupal site:checkout ${SITENAME} -B $2"
fi

$CMD && \
site_rebuild ${SITENAME}
