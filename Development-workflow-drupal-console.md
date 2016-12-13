# Development workflow with Drupal console

We use a number of bash scripts in our current Drupal 7 development environment to do a number of tasks which include:
- Check out the site’s repo
- Run drush make
- Run npm and grunt
- Create symlinks
- Import the database
- Run updates

Because it is a monolithic gismo, it’s not possible to only run commands individually, you get all or nothing. These scripts are currently managed by our DevOps which are quite happy to maintain and introduce new functionalities, but this really should be a dev job and it should be done in PHP.

We also wanted to improve the process for creating new Drupal sites across our infrastructure (Dev, QA, CI, Stage, Live). Our existing process which dates back over 5 years, does not allow the creation of a site from a template, does not allow the setup of a site on a developer environment without that site being installed on the live (non-live) environment and involves a lot of DevOps time to configure the various Jenkins and other scripts used in our development workflow. The process is also prone to human error.

We always wanted to re-write it and now that Drupal console is the new trend, we saw an opportunity to extend it ([check this article to see how we extended Drupal console](https://github.com/dennisinteractive/drupal_console_commands/blob/gh-pages/Extending-drupal-console.md)), keeping in mind what we were trying to improve:
- [Easier way of starting new sites](#head-starting-new-sites)
- [Easier way of working on existing sites](#head-existing-sites)

## <a name="head-starting-new-sites">Starting new sites</a>

I first looked at Composer template for Drupal projects (https://github.com/drupal-composer/drupal-project ) which works, but it’s really just a starting point.

I was going to fork it when I found this fork https://github.com/pfrenssen/drupal-project by pfrenssen that had some nice additions like php unit and behat.

There was also a script handler that automates the creation of settings.php https://github.com/pfrenssen/drupal-project/blob/8.x/scripts/composer/ScriptHandler.php, since I liked this, I decided to fork it and add some extra functionality to add the includes for local configurations, so you would commit *settings.php* but not the includes, since they are relevant to the environment only).These includes are also added to *.gitignore*.

Here is how *settings.php* will be modified:

File: *web/sites/settings.php*
```php
...
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}
if (file_exists(__DIR__ . '/settings.memcached.php')) {
  include __DIR__ . '/settings.memcached.php';
}
if (file_exists(__DIR__ . '/settings.db.php')) {
  include __DIR__ . '/settings.db.php';
}
if (file_exists(__DIR__ . '/settings.dev.php')) {
  include __DIR__ . '/settings.dev.php';
}
```

### 1. Start the site from a template
I tried to use site:new out of the box but the project template was hard coded as **drupal-composer/drupal-project**. Then I started using our patched version of [*site:new*](https://github.com/dennisinteractive/drupal_console_commands/blob/gh-pages/Extending-drupal-console.md#cmd-site-new) but with [ChainRegister](https://github.com/dennisinteractive/drupal_console_commands/blob/gh-pages/Extending-drupal-console.md#how-it-works) I can create new sites using chain command: *chain:site:new*.

`drupal chain:site:new`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/new%20sites/1%20drupal%20chain-site-new.png)
 
### 2. Push the code to the repo
Now go into the site folder, edit composer.json, remove the **ScriptHandler** scripts, change all the details with the relevant information for the site.

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/new%20sites/2%20commit%20your%20code.png)

Commit the code.

```bash 
git init 
git add .  
git remote add origin [url]
```

### 3. Create site-name.yml
Now you can create *~/.console/sites/site-name.yml*. I usually clone another yml as a starting point.
[See an example here](https://github.com/dennisinteractive/drupal_console_commands/blob/gh-pages/Extending-drupal-console.md#file-subscriptions-yml)

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/new%20sites/3%20site.yml.png)

I have some plans to automate the generation of the yml file for the site based on the parameters used in *chain:site:new*.

### 4. <a name="cmd-db-settings">Db Settings</a>
This command will create **settings.db.php** on the same folder where settings.php is.

`drupal site:settings:db`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/new%20sites/4%20db%20settings.png)

```
Arguments:
  name                                                 The site name that is mapped to a repo in sites.yml
  profile                                              commands.site.install.arguments.profile


Options:
  -D, --destination-directory[=DESTINATION-DIRECTORY]  Specify the destination of the site if different than the global destination found in sites.yml
      --langcode=LANGCODE                              commands.site.install.arguments.langcode
      --db-type=DB-TYPE                                commands.site.install.arguments.db-type
      --db-file=DB-FILE                                commands.site.install.arguments.db-file
      --db-host[=DB-HOST]                              commands.migrate.execute.options.db-host
      --db-name[=DB-NAME]                              commands.migrate.execute.options.db-name
      --db-user[=DB-USER]                              commands.migrate.execute.options.db-user
      --db-pass[=DB-PASS]                              commands.migrate.execute.options.db-pass
      --db-prefix[=DB-PREFIX]                          commands.migrate.execute.options.db-prefix
      --db-port[=DB-PORT]                              commands.migrate.execute.options.db-port
      --site-name=SITE-NAME                            commands.site.install.arguments.site-name
      --site-mail=SITE-MAIL                            commands.site.install.arguments.site-mail
      --account-name=ACCOUNT-NAME                      commands.site.install.arguments.account-name
      --account-mail=ACCOUNT-MAIL                      commands.site.install.arguments.account-mail
      --account-pass=ACCOUNT-PASS                      commands.site.install.arguments.account-pass
```

### 5. Site install
Now it’s time to install the site, it can be done either via Browser, using Drupal console or Drush. Go inside the site’s folder and run:

`drupal site:install profile-name`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/new%20sites/5%20site%20install.png)

### 6. Database dump
This can be done either using Drupal console or Drush. Go inside the site’s folder and run:

`drupal database:dump --file=filename`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/new%20sites/6%20db%20dump.png)


## <a name="head-existing-sites">Working on existing sites</a>

### 1. Listing available sites

This command will list site information for all yml files found in *~/.console/sites*.

`drupal site:debug`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/existing%20sites/1%20site%20debug.png)

### 2. Checking out a repo
This command will checkout the repo configured in *~/.console/sites*.

`drupal site:checkout new-site`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/existing%20sites/2%20site%20checkout.png)

```
Arguments:
  name                                                 The site name that is mapped to a repo in sites.yml
Options:
  -D, --destination-directory[=DESTINATION-DIRECTORY]  Specify the destination of the site if different than the global destination found in sites.yml
      --ignore-changes                                 Ignore local changes when checking out the site
  -B, --branch[=BRANCH]                                Specify which branch to checkout if different than the global branch found in sites.yml
```

### 3. Composer
This command will run composer install if it cannot find composer.lock, if found, it will run composer update.

`drupal site:compose new_site`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/existing%20sites/3%20site%20compose.png)

```
Arguments:
  name                                                 The site name that is mapped to a repo in sites.yml


Options:
  -D, --destination-directory[=DESTINATION-DIRECTORY]  Specify the destination of the site if different than the global destination found in sites.yml
```

### 4. Db settings
Same as in [Starting new sites](#cmd-db-settings)

`drupal site:settings:db`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/new%20sites/4%20db%20settings.png)

### 5. Db Import
This command will drop the current database and import the dump provided.
The db-dump location comes from *~/.console/sites/site-name.yml*.

The command will copy the dump from the original place to **/tmp**. If you run the command again, it will only copy the dump again once it has changed. This is very useful when working remotely or on slow networks.

If no db-dump information is available or there is no dump at the location, it will run a site install.
Supported extensions: **.sql**, **.sql.gz**.

`drupal site:db:import`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/existing%20sites/5%20db%20import.png)

This command provides the same arguments and options as *site:db:settings*.


## Doing everything with a single command
And finally, putting all together into one place. I created chain:site:build that contains calls to Drupal console commands, custom commands and calls to another chains:

File: *chain-site-build.yml*

```javascript
commands:
# Checkout site
  - command: site:checkout
    arguments:
      name: '%{{name}}'
    options:
      destination-directory: '/var/www/%{{name}}'
      branch: '%{{branch|8.x}}'
# Run composer
  - command: site:compose
    arguments:
      name: '%{{name}}'
# Run npm
  - command: chain:site:npm
    options:
      placeholder:
        - 'name:%{{name}}'
# Run grunt
  - command: chain:site:grunt
    options:
      placeholder:
        - 'name:%{{name}}'
# Create settings.db.php
  - command: site:settings:db
    arguments:
      name: '%{{name}}'
    options:
      db-name: '%{{name}}'
      db-user: 'root'
      db-pass: ''
      db-host: '127.0.0.1'
      db-port: '3306'
# Create settings.memcache.php
  - command: site:settings:memcache
    arguments:
      name: '%{{name}}'
    options:
      memcache-prefix: '%{{name}}'
# Imports db or installs a site
  - command: site:db:import
    arguments:
      name: '%{{name}}'
      profile: '%{{profile|config_installer}}'
    options:
      account-pass: demo
# Run updates
  - command: exec
    arguments:
      bin: 'cd /vagrant/repos/%{{name}}/web; drush updb -y;'
# Clear cache
  - command: exec
    arguments:
      bin: 'cd /vagrant/repos/%{{name}}/web; drush cr;'
```

`drupal chain:site:build`

This command will perform the following tasks:
- Checkout the repo
- Run composer install/update
- Run npm and grunt
- Create settings.db.php
- Create settings.memcache.php
- Run a site install if no db-dump is available otherwise it will import the dump.
- Run updates
- Clear the cache

### Listing commands

`drupal list site`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/drupal-list-site-after.png)

`drupal list chain`

![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/drupal-list-chain.png)


## Provisioning

By this time I already had all the bits I needed, so I started talking to our DevOps team to add to the VM.
I created the dev_scripts https://github.com/dennisinteractive/dev_scripts project which is a wrapper that installs Drupal console and the Custom commands + chains.

The installation is quite simple and happens once when provisioning runs for the first time on the VM:

`composer global create-project dennisdigital/dev_scripts:dev-master`

During the installation, composer runs a script https://github.com/dennisinteractive/dev_scripts/tree/e60f47b9f10b8ad90a855e52d94319a2a9caf57a that will take care of preparing the Drupal console environment.

```bash
#!/bin/sh
pwd=`pwd`

# Remove existing symlink
echo "[Info] Creating symlink. You might need to type your password to use sudo"
if [ -L /usr/local/bin/drupal ]; then sudo rm /usr/local/bin/drupal; fi
# Create symlink
sudo ln -s ${pwd}/vendor/bin/drupal /usr/local/bin/drupal

# Drupal commands
echo "[Info] Drupal init"
drupal init --override
drupal settings:set environment dev

# Append custom config.yml to the global config (temporarily)
cat ${pwd}/vendor/dennisdigital/drupal_console_commands/config.yml >> ~/.console/config.yml

# Copy chain.yml to home folder (temporarily)
cp ${pwd}/vendor/dennisdigital/drupal_console_commands/chain.yml ~/.console/


As a temporary fix, until commands run as services, we are appending the contents of config.yml onto ~/.console/config.yml https://github.com/dennisinteractive/dev_scripts/blob/e60f47b9f10b8ad90a855e52d94319a2a9caf57a/scripts/post-create-project.sh#L16 
```

Also, chain.yml will no longer be needed when we have commands as services https://github.com/hechoendrupal/DrupalConsole/issues/1947

The provisioning will checkout the list of sites (yml) into *~/.console/sites* and we are ready to go.

<a href="https://www.youtube.com/watch?v=Y1Thr6qTvxg
">![](https://github.com/dennisinteractive/drupal_console_commands/raw/gh-pages/images/video.png)</a>

## Roadmap
- Upgrade dev_scripts to use the latest Drupal console codebase (requires command as services, ability to install Drupal console commands globally, independent of drupal core)
- Use console-core and console-launcher instead of DrupalConsole
- Use site aliases on all chain commands, then we don’t need to change folders with cd (requires 1.0.0-rc9)

## Deploying to production
### To be continued
		
## Repos
- Drupal Console https://github.com/hechoendrupal/DrupalConsole
- Dev scripts https://github.com/dennisinteractive/dev_scripts
- Drupal Console Commands: https://github.com/dennisinteractive/drupal_console_commands
- Drupal Project: https://github.com/dennisinteractive/drupal-project

## Issues
- Use Chain as actual command https://github.com/hechoendrupal/DrupalConsole/issues/1898
- Chain commands are not working when passing one placeholder and --no-interaction https://github.com/hechoendrupal/DrupalConsole/issues/2964

## Pull requests
- Chain Register: https://github.com/hechoendrupal/DrupalConsole/pull/2961
- Comments in yml: https://github.com/hechoendrupal/DrupalConsole/pull/2963

## Chat about Drupal console
- Drupal Console on Slack: https://drupal.slack.com/archives/drupal-console
- Drupal Console on Gitter: https://gitter.im/hechoendrupal/DrupalConsole

## About me

![](http://marcelovani.eu/images/me-350.jpg)

## Marcelo Vani
### Software engineer
[@marcelovani](https://twitter.com/marcelovani "@marcelovani")

[http://marcelovani.eu](http://marcelovani.eu)

### Dennis Publishing
![](http://cdn2.dennis.co.uk/sites/denniscorporate/themes/denniscorporate/logo.png)
