# Drupal console commands

Provides custom Drupal console commands and chains.

## Requirements
- Composer https://getcomposer.org/
- Drupal console https://drupalconsole.com/

## Installation

curl -L https://goo.gl/UnjUuW | sh

## Commands
These are custom commands used to build a site. The information about the site comes from ~/.console/sites/site-name.yml.
e.g. https://raw.githubusercontent.com/dennisinteractive/drupal_console_commands/master/example/site-example.yml

- drupal **site:new**
	Builds a new site using Drupal project as template https://github.com/dennisinteractive/drupal-project

- drupal **site:checkout** *site-name*
	Performs a git clone and checks out the specified branch

- drupal **site:compose** *site-name*
	Runs *composer*

- drupal **site:npm**
  Runs NPM

- drupal **site:grunt**
  Runs Grunt

- drupal **site:settings:db** *site-name*
	Creates *settings.db.php* in the *web/sites/default* folder. This file contains DB credentials and should not be committed.

- drupal **site:settings:local** *site-name*
	Creates *settings.local.php* in the *web/sites/default* folder. This file contains local settings overrides and should not be committed.

- drupal **site:settings:memcache** *site-name*
	Creates *settings.memcache.php* in the *web/sites/default* folder. This file contains Memcache configuration and should not be committed.

- drupal **site:drush:alias** *site-name*
	Sets up drush aliases

- drupal **site:phpunit:setup** *site-name*
	Creates *phpunit.xml* in the root. This file contains PHPUnit configuration and should not be committed.

- drupal **site:behat:setup** *site-name*
	Creates *behat.yml* in the *tests* folder. This file contains Behat configuration and should not be committed.

- drupal **site:db:import** *site-name*
	If a database dump is available, it will drop the current database and import the dump. The db-dump information comes from *~/.console/sites/site-name.yml*.
	The command will copy the dump from the original place to */tmp*. If you run the command again, it will only copy the file once the original has changed. This is very useful when working remotely on slow networks.
	If no db-dump information is available or there is no dump at the location, it will run a site install.
	Supported extensions: **.sql**, **.sql.gz**.
	
- drupal **site:update** *site-name*
  Used to run updates and import configuration
      - drush site-set @site (Set default drush alias) 
      - drush sset system.maintenance_mode 1 (Enable maintenance mode)
      - drush cr (Clear caches) 
      - drush updb -y (Runs updates)
      - drush cim -y; drush cim -y (Import configuration - twice to fix a problem with config import when new modules are added to core.extensions.yml)
      - drush sset system.maintenance_mode 0 (Disable maintenance mode)
      - drush cr (Clear caches)
      
- drupal **site:test** *site-name*
      Runs test suites
      - ./behat %s' (Runs behat tests)
      - ./vendor/bin/phpunit (Runs phpunit tests)

## Chains
Chains that can be reused on various environments

- drupal **site:configure** A chain that will call all the commands below:
    - site:settings:db
    - site:settings:local
    - site:settings:memcache
    - site:drush:alias

- drupal **site:test:setup** Sets the test suites
    - site:phpunit:setup
    - site:behat:setup

## Environment specific chains
Each environment will have its own chain that executes the relevant commands and chains

### Dev
- drupal **site:build** Builds a site for development
    - site:checkout
    - site:rebuild (chain)

- drupal **site:rebuild** Performs necessary steps to rebuild the site from a given source
    - site:compose
    - site:npm
    - site:grunt
    - site:configure (chain)
    - site:test:setup (chain)
    - site:db:import
    - site:update

### Artifact
- drupal **site:build:artifact** Prepare artifacts
    - site:checkout
    - site:compose
    - site:npm
    - site:grunt

### CI
- drupal **site:build:ci** Builds a site for CI
    - site:db:import
    - site:update
    - site:test

### QA
- drupal **site:build:qa** Builds a site for QA
    - site:db:import
    - site:update
    - site:test

### Staging
- drupal **site:build:staging** Builds a site for Staging
    - site:db:import
    - site:update

### Production
- drupal **site:build:prod** Runs updates on production
  - site:update

## Useful arguments and options
- **-h** Show all the available arguments and options
- **--no-interaction** Will execute the command without asking any optional argument

## Environment variables
By default, the commands will use parameters from the site.yml, but it is possible to override them using environment variables.

For example, to override the root directory you can set the variable before calling `site:build`

`export site_destination_directory="/directory/"`

## Usage example
```
drupal site:build
drupal site:db:import [site_name]
```
