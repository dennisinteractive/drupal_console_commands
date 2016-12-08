<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteBaseCommand.
 *
 * Base class for site commands.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteBaseCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteBaseCommand extends Command {
  use CommandTrait;
  /**
   * IO interface.
   *
   * @var null
   */
  protected $io = NULL;
  /**
   * Global location for sites.yml.
   *
   * @var array
   */
  protected $configFile = NULL;
  /**
   * Stores the contents of sites.yml.
   *
   * @var array
   */
  protected $config = NULL;
  /**
   * Stores the site name.
   *
   * @var string
   */
  protected $siteName = NULL;
  /**
   * Stores the profile name.
   *
   * @var string
   */
  protected $profile = NULL;
  /**
   * Stores the destination directory.
   *
   * @var string
   */
  protected $destination = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('site_base');

    $this->addArgument(
      'name',
      InputArgument::REQUIRED,
      // @todo use: $this->trans('commands.site.checkout.name.description')
      'The site name that is mapped to a repo in sites.yml'
    );

    $this->addOption(
      'destination-directory',
      '-D',
      InputOption::VALUE_OPTIONAL,
      // @todo use: $this->trans('commands.site.checkout.name.options')
      'Specify the destination of the site if different than the global destination found in sites.yml'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $this->io = new DrupalStyle($input, $output);

    $name = $input->getArgument('name');
    if (!$name) {
        $name = $this->io->ask($this->trans('Site name (In small letters. Don\'t use spaces or hyphens, use underlines.)'));

        $input->setArgument('name', $name);
    }

    $this->validateSiteParams($input, $output);

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->validateSiteParams($input, $output);
  }

  /**
   * Helper to copy the arguments from another class.
   */
  public function inheritArguments(\Symfony\Component\Console\Command\Command $command) {
    foreach ($command->getDefinition()->getArguments() as $argument) {
      if (!$this->getDefinition()->hasArgument($argument->getName())) {
        $this->getDefinition()->addArgument($argument);
      }
    }
  }

  /**
   * Helper to copy the options from another class.
   */
  public function inheritOptions(\Symfony\Component\Console\Command\Command $command) {
    foreach ($command->getDefinition()->getOptions() as $option) {
      if (!$this->getDefinition()->hasOption($option->getName())) {
        $this->getDefinition()->addOption($option);
      }
    }
  }

  /**
   * Helper to validate parameters.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  protected function validateSiteParams(InputInterface $input, OutputInterface $output) {
    $this->io = new DrupalStyle($input, $output);

    // Get config.
    $this->_siteConfig($input);

    // Validate profile.
    $this->_validateProfile($input);

    // Validate destination.
    $this->_validateDestination($input);
  }

  /**
   * Helper to check that the config file exits and load the configuration.
   *
   * @param InputInterface $input
   *
   * @return $this
   *
   * @throws SiteCommandException
   */
  protected function _siteConfig(InputInterface $input) {
    $siteName = $input->getArgument('name');

    $application = $this->getApplication();
    $application->getConfig()->loadSite($siteName);

    // Site environment from config.yml.
    $config = $application->getConfig()->get(sprintf(
      'sites.%s.%s',
        $siteName,
        $input->getOption('env')
      )
    );

    if (empty($config)) {
      $message = sprintf(
        'Site not found. To see a list of available sites, run %s',
        'drupal site:debug'
      );
      throw new SiteCommandException($message);
    }

    // Update input.
    $input->setArgument('name', $siteName);
    $this->siteName = $siteName;
    $this->config = $config;

    return $this;
  }

  /**
   * Helper to validate profile parameter.
   *
   * @param InputInterface $input
   *
   * @return string Profile
   */
  protected function _validateProfile(InputInterface $input) {
    if ($input->hasArgument('profile') &&
      !is_null($input->getArgument('profile'))
    ) {
      // Use config from parameter.
      $this->profile = $input->getArgument('profile');
    }
    elseif (isset($this->config['profile'])) {
      // Use config from sites.yml.
      $this->profile = $this->config['profile'];
    }
    else {
      $this->profile = 'config_installer';
    }

    // Update input.
    if ($input->hasArgument('profile')) {
      $input->setArgument('profile', $this->profile);
    }

    return $this->profile;
  }

  /**
   * Helper to validate destination parameter.
   *
   * @param InputInterface $input
   *
   * @throws SiteCommandException
   *
   * @return string Destination
   */
  protected function _validateDestination(InputInterface $input) {
    if ($input->hasOption('destination-directory') &&
      !is_null($input->getOption('destination-directory'))
    ) {
      // Use config from parameter.
      $this->destination = $input->getOption('destination-directory');
    }
    elseif (isset($this->config['root'])) {
      // Use config from sites.yml.
      $this->destination = $this->config['root'];
    }
    else {
      $this->destination = '/tmp/' . $this->siteName;
    }

    // Make sure we have a slash at the end.
    if (substr($this->destination, -1) != '/') {
      $this->destination .= '/';
    }

    // Append site name.
    if (strpos($this->destination, $this->siteName, 0) === FALSE) {
      $this->destination .= $this->siteName . '/';
    }

    return $this->destination;
  }

  /**
   * Helper to return the path to settings.php
   * It will try to match a folder with same name as site name
   * If not found, it will try to match a folder called "default".
   *
   * @return string Path
   */
  public function settingsPhpDirectory() {
    $webSitesPath = $this->destination . 'web/sites/';
    $settingsPath = $webSitesPath . 'default';

    $command = sprintf(
      'cd %s; find . -name settings.php',
      $webSitesPath
    );

    $this->io->comment('Searching for settings.php in the sites folder');
    $shellProcess = $this->get('shell_process');
    if ($shellProcess->exec($command, TRUE)) {
      if (!empty($shellProcess->getOutput())) {
        $output = $shellProcess->getOutput();

        // Regex to match.
        $siteName = $this->siteName;
        $regex = array (
          "|^./($siteName)/settings.php|m",
          "|^./(default)/settings.php|m"
        );
        foreach ($regex as $r) {
          preg_match_all($r, $output, $matches);
          if (!empty($matches[0])) {
            $settingsPath = $webSitesPath . reset($matches[1]);
            break;
          }
        }
      }
    }

    // Make sure we have a slash at the end.
    if (substr($settingsPath, -1) != '/') {
      $settingsPath .= '/';
    }

    return $settingsPath;
  }
}
