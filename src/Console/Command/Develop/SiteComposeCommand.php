<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteComposeCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Config;

/**
 * Class SiteComposeCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteComposeCommand extends Command {
  use CommandTrait;
  /**
   * IO interface.
   *
   * @var null
   */
  private $io = NULL;
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
   * Stores the site name.
   *
   * @var string
   */
  private $siteName = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('site:compose')
      // @todo use: ->setDescription($this->trans('commands.site.compose.description'))
      ->setDescription('Runs composer installer')
      ->addArgument(
        'site-name',
        InputArgument::REQUIRED,
        // @todo use: $this->trans('commands.site.compose.site-name.description')
        'The site name that is mapped to a repo in sites.yml'
      )->addOption(
        'destination-directory',
        '-D',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.compose.site-name.options')
        'Specify the destination of the site compose if different than the global destination found in sites.yml'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $this->siteName = $input->getArgument('site-name');

    $io = new DrupalStyle($input, $output);
    $ymlFile = new Parser();
    $config = new Config($ymlFile);
    $configFile = $config->getUserHomeDir() . '/.console/sites.yml';

    // Check if configuration file exists.
    if (!file_exists($configFile)) {
      $io->error(sprintf('Could not find any configuration in %s', $configFile));
      exit;
    }
    $this->configFile = $configFile;
    $this->config = $config->getFileContents($configFile);

    if (empty($this->siteName)) {
      $io->writeln(sprintf('Site not found in /.console/sites.yml'));
      $io->writeln(sprintf('Available sites: [%s]', implode(', ',
          array_keys($this->config['sites'])))
      );
      exit;
    }

    // Load site config from sites.yml.
    if (!isset($this->config['sites'][$this->siteName])) {
      $io->error(sprintf('Could not find any configuration for %s in %s',
          $this->siteName,
          $this->configFile)
      );
      exit;
    }

    // Load default destination directory.
    $dir = '/tmp/' . $this->siteName . '/';
    if (isset($this->config['global']['destination-directory'])) {
      $dir = $this->config['global']['destination-directory'] .
        '/' . $this->siteName . '/';
    }
    // Overrides default destination directory.
    if ($input->getOption('destination-directory')) {
      $dir = $input->getOption('destination-directory');
    }
    $input->setOption('destination-directory', $dir);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $this->io = new DrupalStyle($input, $output);

    $destination = $input->getOption('destination-directory');
    // Make sure we have a slash at the end.
    if (substr($destination, -1) != '/') {
      $destination .= '/';
    }

    // Compose site.
    if (!file_exists($destination . 'composer.json')) {
      $this->io->error(sprintf('The file composer.json is missing on %s', $destination));
      exit;
    }
    else {
      // Run composer install.
      $this->composerInstall($destination);
    }

  }

  /**
   * Helper to run composer install.
   *
   * @param $destination The destination folder.
   *
   * @return bool TRUE or FALSE;
   */
  protected function composerInstall($destination) {
    $command = sprintf('cd %s; composer install',
      $destination
    );

    $shellProcess = $this->get('shell_process');

    if ($shellProcess->exec($command, TRUE)) {
      // All good, no output.
      $this->io->success('Composer install finished');
    }
    else {
      // Show error message.
      $this->io->error($shellProcess->getOutput());

      return FALSE;
    }

    return TRUE;
  }
}
