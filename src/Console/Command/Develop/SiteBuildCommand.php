<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteBuildCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Config;

/**
 * Class SiteBuildCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteBuildCommand extends Command {
  use CommandTrait;
  /**
   * Global location for sites.yml.
   *
   * @var array
   */
  private $configFile = NULL;
  /**
   * Stores the contents of sites.yml.
   *
   * @var array
   */
  private $config = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('site:build')
      // @todo use: ->setDescription($this->trans('commands.site.build.description'))
      ->setDescription(t('Check out a site and installs composer.json.'))
      ->addArgument(
        'site-name',
        InputArgument::REQUIRED,
        // @todo use: $this->trans('commands.site.build.site-name.description')
        t('The site name that is mapped to a repo in sites.yml.')
      )->addOption(
        'destination-directory',
        '-dd',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.build.site-name.options')
        t('Specify the destination of the site build if different than the global destination found in sites.yml')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    $ymlFile = new Parser();
    $config = new Config($ymlFile);
    $configFile = $config->getUserHomeDir() . '/.console/sites.yml';

    // Check if configuration file exists.
    if (!file_exists($configFile)) {
      $io->simple(t('Could not find any configuration in :file', array(':file' => $configFile)));

      return FALSE;
    }
    $this->configFile = $configFile;
    $this->config = $config->getFileContents($configFile);

    // Overrides default destination directory.
    if (!$input->getOption('destination-directory')) {
      // Load default destination directory.
      if (isset($this->config['global']['destination-directory'])) {
        $input->setOption('destination-directory', $this->config['global']['destination-directory']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    /* Register your command as a service
     *
     * Make sure you register your command class at
     * config/services/namespace.yml file and add the `console.command` tag.
     *
     * develop_example:
     *   class: VM\Console\Command\Develop\SiteBuildCommand
     *   tags:
     *     - { name: console.command }
     *
     * NOTE: Make the proper changes on the namespace and class
     *       according your new command.
     *
     * DrupalConsole extends the SymfonyStyle class to provide
     * an standardized Output Formatting Style.
     *
     * Drupal Console provides the DrupalStyle helper class:
     */
    $io = new DrupalStyle($input, $output);
    $siteName = $input->getArgument('site-name');

    $io->simple(t('Building :site', array(':site' => $siteName)));

    // Load site config from sites.yml.
    if (isset($this->config['sites'][$siteName])) {
      $siteConfig = $this->config['sites'][$siteName];
    }
    else {
      $io->simple(t('Could not find any configuration for :site in :file',
          array(':site' => $siteName, ':file' => $this->configFile))
      );
      return FALSE;
    }

    foreach ($siteConfig as $key => $item) {
      var_dump($item);
    }
  }
}
