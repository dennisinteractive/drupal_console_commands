# Drupal console commands

Provides custom Drupal console commands and chains.

## Requirements
- Composer https://getcomposer.org/
- Drupal console https://drupalconsole.com/

## Installation

curl -L https://goo.gl/DcpKPN | sh

or install it manually:

### Requirements:
 - Composer: https://getcomposer.org/download/
 - Launcher: https://hechoendrupal.gitbooks.io/drupal-console/content/en/getting/launcher.html

### Run the following:
- Init Drupal
```
drupal -n --override init
```

- Set the environment i.e.
```
drupal settings:set environment dev
```

- Install the Drupal Extend plugin (https://github.com/hechoendrupal/drupal-console-extend)
```cd ~/.console/
composer create-project \
drupal/console-extend ~/.console/extend \
--no-interaction
```

- Remove example (optional)
```
cd ~/.console/extend;
composer remove drupal/console-extend-example
```

- Install Dennis Digital Commands
```
cd ~/.console/extend;
composer require dennisdigital/drupal_console_commands:extend_properly-dev
```

- Copy chain commands
```
cp vendor/dennisdigital/drupal_console_commands/chain/*.yml ~/.console/chain
```

## Commands
These are custom commands used to build a site. The information about the site comes from ~/.console/sites/site-name.yml.
See some example site yml on https://github.com/dennisinteractive/drupal_console_commands/tree/master/sites

- drupal **site:build** Runs the following commands to build a site:
    - site:checkout
    - site:compose|make
    - site:npm
    - site:grunt
    - site:settings
    - site:phpunit:setup
    - site:behat:setup
    - site:db:import
    - site:update
    options:
    - skip: Used to skip one or more commands. i.e. --skip="checkout, phpunit:setup"'

- drupal **site:new**
	Builds a new site using Drupal project as template https://github.com/dennisinteractive/drupal-project

- drupal **site:checkout** *site-name* [--branch|--tag]
	Performs a git clone and checks out the specified branch or tag

- drupal **site:checkout:tag** *site-name* --tag
	Performs a git clone and checks out the specified tag/revision

- drupal **site:checkout:branch** *site-name* --branch
	Performs a git clone and checks out the specified branch

- drupal **site:compose** *site-name* Runs composer install
	Runs *composer*

- drupal **site:make** *site-name* Runs drush make
	Runs *drush make*

- drupal **site:npm** Compiles npm packages
  Runs NPM

- drupal **site:grunt** Compiles grunt packages
  Runs Grunt

- drupal **site:settings** *site-name*
    - Runs `site:settings:db`
    - Runs `site:settings:memcache`
    - Creates *settings.php* using *default.settings.php* provided by Drupal core, then appends includes for the settings below:
      - *settings.db.php* Database credentials.
      - *settings.memcache.php* Memcache configuration.
	  - *settings.local.php* in the *web/sites/[site name]* directory. This file is auto-generated and should not be committed. If you have a file named `web/sites/example.settings.local.php` on the site's folder, it will be used as a template for settings.local.php.
	  - *settings.[env].php* These files are auto-generated and should not be committed. Depending on your environment (--env option), it will copy the respective file into *web/sites/[site name]*. i.e. default.settings.dev.php -> settings.dev.php
	  - *settings.mine.php* Use this file to add your personal customisations to override all of the above.

  **It is recommended to add settings.*.php to .gitignore.**

- drupal **site:settings:db** *site-name*
	Creates *settings.db.php* in the *web/sites/default* folder. This file contains DB credentials and should not be committed.

- drupal **site:settings:memcache** *site-name*
	Creates *settings.memcache.php* in the *web/sites/default* folder. This file contains Memcache configuration and should not be committed.

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
  Used to run updates, import configuration, clear caches
  You can enable/disable modules after import by adding the list to the site.yml file. i.e.
  ```
    modules:
      enable:
        - stage_file_proxy
        - devel
      disable:
        - cdn
  ```

- drupal **site:test** *site-name*
      Runs test suites
      - ./behat %s' (Runs behat tests)
      - ./vendor/bin/phpunit (Runs phpunit tests)

## Environment specific chains
Each environment will have its own chain that executes the relevant commands and chains

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
- **-h** Show all the available arguments and options.
- **--no-interaction** Will execute the command without asking any optional argument
- **--skip** (site:build) Skips the execution of one or more commands.

## Environment variables
By default, the commands will use parameters from the site.yml, but it is possible to override them using environment variables.

For example, to override the root directory you can set the variable before calling `site:build`

`export site_destination_directory="/directory/"`

## Usage examples
```
drupal site:build
drupal site:build d7-example
drupal site:build d7-example -e dev --branch="master"
drupal site:build d7-example -e dev --branch="master" --skip="checkout, compose"
drupal site:db:import d7-example
```
