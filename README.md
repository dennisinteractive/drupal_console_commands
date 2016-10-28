# dev_scripts

Provides custom Drupal console commands.

- site:build [site_name]
	Will do the following:
	- Check out the site's repo
	- Run the make files

- site:db:import [site_name]
	Will do the following:
	- Import the latest Db dump
	- Run Db updates

Installation
============

Put the contents of config.yml inside config.yml
i.e. cat config.yml >> ~/.console/config.yml

Usage
=====
drupal site:build [site_name]
drupal site:db:import [site_name]
