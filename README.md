# Drupal console commands

Provides custom Drupal console commands and chains.

## Requirements
- Composer https://getcomposer.org/
- Drupal console https://drupalconsole.com/

## Installation

curl -L https://goo.gl/UnjUuW | sh

# Commands
These are custom commands used to build a site. The information about the site comes from ~/.console/sites/site-name.yml.
e.g. https://raw.githubusercontent.com/dennisinteractive/drupal_console_commands/master/example/site-example.yml

- drupal **site:new**
	Builds a new site using Drupal project as template https://github.com/dennisinteractive/drupal-project
- drupal **site:checkout** *site-mame*
	Performs a git clone and checks out the specified branch
- drupal **site:compose** *site-name*
	Runs *composer install*. Alternatively, it will run *composer update* if there is a composer.lock.
- drupal **site:settings:db** *site-name*
	Creates *settings.db.php* in the *web/sites/default* folder. This file contains DB credentials and should not be committed.
- drupal **site:phpunit:setup** *site-name*
	Creates *phpunit.xml* in the root. This file contains PHPUnit configuration and should not be committed.
- drupal **site:behat:setup** *site-name*
	Creates *behat.yml* in the *tests* folder. This file contains Behat configuration and should not be committed.
- drupal **site:settings:memcache** *site-name*
	Creates *settings.memcache.php* in the *web/sites/default* folder. This file contains Memcache configuration and should not be committed.
- drupal **site:db:import** *site-name*
	If a database dump is available, it will drop the current database and import the dump. The db-dump information comes from *~/.console/sites/site-name.yml*.
	The command will copy the dump from the original place to */tmp*. If you run the command again, it will only copy the file once the original has changed. This is very useful when working remotely on slow networks.
	If no db-dump information is available or there is no dump at the location, it will run a site install.
	Supported extensions: **.sql**, **.sql.gz**.
- drupal **site:npm** *site-name*
	Runs npm install
- drupal **site:grunt** *site-name*
	Runs grunt
- drupal **site:build**
	A wrapper that will call all the commands below:
    - site:checkout
    - site:rebuild
- drupal **site:rebuild**
	A wrapper that will call all the commands below:
    - site:compose
    - site:npm
    - site:grunt
    - site:settings:db
    - site:phpunit:setup
    - site:behat:setup
    - site:settings:memcache
    - site:db:import
    - drush updb
    - drush cr

# Useful arguments and options
- **-h** Show all the available arguments and options
- **--no-interaction** Will execute the command without asking any optional argument

# Usage example
```
drupal site:build
drupal site:db:import [site_name]
```
