# Dennis Console

Provides custom Drupal console commands and chains.

## Requirements

- [Composer](https://getcomposer.org/download/)

## Installation

```
curl -L https://raw.githubusercontent.com/dennisinteractive/drupal_console_commands/master/Makefile > Makefile

make install
```
	
## Commands

These are custom commands used to build a site. The information about the site comes from `~/.console/sites/site-name.yml`.
See some examples of site YAML files at [https://github.com/dennisinteractive/drupal_console\_commands/tree/master/sites](https://github.com/dennisinteractive/drupal_console_commands/tree/master/sites)

- drupal **site:build** 

	Runs the following commands to build a site:
    
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
    
    - skip: Use this option to leave out one or more commands. i.e. `--skip="checkout, phpunit:setup"` will run all the steps above except`site:checkout` and `site:phpunit:setup`.

- drupal **site:new**
	
	Builds a new site using a Drupal project as template from one of the following:
		
	- drupal-composer/drupal-project:8.x-dev
	- acquia/lightning-project
	- acquia/reservoir-project
	- burdamagazinorg/thunder-project

- drupal **site:checkout** *site-name* [--branch|--tag]
	
	Performs a git clone and checks out the specified branch or tag

	- drupal **site:checkout:tag** *site-name* --tag
	
		Performs a `git clone` and checks out the specified tag/revision

	- drupal **site:checkout:branch** *site-name* --branch
	
		Performs a `git clone` and checks out the specified branch

- drupal **site:compose** *site-name* 

	Runs `composer install`
	
- drupal **site:make** *site-name* 

	Runs `drush make`

- drupal **site:npm** 

	Compiles NPM packages and runs `npm`

- drupal **site:grunt** 

	Compiles Grunt packages and runs `grunt`

- drupal **site:settings** *site-name*
    
    - Runs the following commands:
    	
    	- site:settings:db
    	- site:settings:memcache
    	
    - Creates *settings.php* using *default.settings.php* provided by Drupal core
    - Appends includes for the settings below:
    	
      - *settings.db.php*  for database credentials.
      - *settings.memcache.php* for memcache configuration.
	   - *settings.local.php* in the *web/sites/[site name]* directory. 
	  
		This file is auto-generated and should not be committed. If you have a file named `web/sites/example.settings.local.php` in the site's folder, it will be used as a template for `settings.local.php`.
	  
	  - *settings.[env].php* These files are auto-generated and should not be committed. Depending on your environment (--env option), it will copy the respective file into *web/sites/[site name]*. i.e. default.settings.dev.php -> settings.dev.php
	  - *settings.mine.php* Use this file to add your personal customisations to override all of the above.

  **It is recommended that you add settings.*.php to .gitignore.**

- drupal **site:settings:db** *site-name*
	
	Creates *settings.db.php* in the *web/sites/default* folder. This file contains DB credentials and should not be committed.

- drupal **site:settings:memcache** *site-name*
	
	Creates *settings.memcache.php* in the *web/sites/default* folder. This file contains Memcache configuration and should not be committed.

- drupal **site:phpunit:setup** *site-name*
	
	Creates *phpunit.xml* in the root. This file contains PHPUnit configuration and should not be committed.

- drupal **site:behat:setup** *site-name*
	
	Creates *behat.yml* in the *tests* folder. This file contains Behat configuration and should not be committed.

- drupal **site:db:import** *site-name*
	
	If a database dump is available, it will drop the current database and import the dump. The information for this comes from `~/.console/sites/site-name.yml`.
	
	The command will copy the dump from the original place to `/tmp`. Runing the command again will only copy the file if the original has changed. This is very useful when working remotely on slow networks.
	
	If no db-dump information is available or there is no dump at the location, it will run a site install.
	
	Currently, the supported extensions are `.sql` and `.sql.gz` only.

- drupal **site:update** *site-name*

  Runs updates, import configuration, clear caches. You can enable or disable modules after import by adding the list to the site.yml file as follows:
  
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
   
      - `./behat %s` (Behat tests)
      - `./vendor/bin/phpunit` (PHPUnit tests)

## Environment specific chains

Each environment will have its own chain that executes the relevant commands and chains

### Artifact

- drupal **site:build:artifact** 
	
	Prepares artifacts and runs the following:
    
    - site:checkout
    - site:compose
    - site:npm
    - site:grunt

### CI
- drupal **site:build:ci** 

	Builds a site for CI and runs the following:
    
    - site:db:import
    - site:update
    - site:test

### QA

- drupal **site:build:qa** 

	Builds a site for QA and runs the following:
    
    - site:db:import
    - site:update
    - site:test

### Staging

- drupal **site:build:staging** 

	Builds a site for Staging and runs the following:
    
    - site:db:import
    - site:update

### Production

- drupal **site:build:prod** 

	Runs updates on production and runs the following:
	
  	- site:update

## Useful arguments and options

- **-h** - Shows all the available arguments and options.
- **--no-interaction** - Executes the command without asking any optional argument
- **--skip** - Skips the execution of one or more commands (only `site:build`).

## Environment variables

By default, the commands will use parameters from the `site.yml`, but it is possible to override them using environment variables.

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
