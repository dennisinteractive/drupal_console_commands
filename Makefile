# https://swcarpentry.github.io/make-novice/02-makefiles/
# Usage: make install
install :
		# composer clearcache
		rm -rf ~/.console/launcher
		rm -rf ~/.console/extend
		rm -rf ~/.console/sites
		rm -rf ~/.console/chain
		sudo rm -rf  /usr/local/bin/drupal

		# Install launcher
		composer create-project --repository='{"type": "vcs", "url": "git@github.com:dennisinteractive/drupal-console-launcher.git", "vendor-alias": "drupal", "no-api": true}' drupal/console-launcher:dev-master#9c7390353c1e839 ~/.console/launcher --no-dev --no-interaction
		sudo ln -s ~/.console/launcher/bin/drupal /usr/local/bin/drupal

		# Configure Drupal Console
		drupal -n --override init
		drupal settings:set environment dev
		drupal settings:set overrides.config.skip-validate-site-uuid true

		# Install Console Extend Plugin
		composer create-project --repository='{"type": "vcs", "url": "git@github.com:dennisinteractive/drupal-console-extend.git", "vendor-alias": "drupal", "no-api": true}' drupal/console-extend:dev-master#041bb9cf9831d ~/.console/extend --no-dev --no-interaction

		# Install Dennis console
		# cd ~/.console/extend && composer require drupal/console:dev-master#368bbfa44dc6
		git clone git@github.com:dennisinteractive/drupal_console_sites.git ~/.console/sites/
		cd ~/.console/extend && composer require dennisdigital/drupal_console_commands:dev-master#aa1083982c19
		cd ~/.console/extend && composer update
		cp ~/.console/extend/vendor/dennisdigital/drupal_console_commands/chain/*.yml ~/.console/chain
		cp ~/.console/extend/vendor/drupal/console/extend.console.services.yml ~/.console/extend
		cp ~/.console/extend/vendor/drupal/console/extend.console.uninstall.services.yml ~/.console/extend
		drupal debug:site
		drupal list site
		echo Drupal Console Installed
