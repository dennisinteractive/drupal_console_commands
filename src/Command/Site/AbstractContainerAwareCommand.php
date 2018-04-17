<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\AbstractCommand.
 *
 * Base class for site commands.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Command\Command;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;
use DennisDigital\Drupal\Console\Utils\ShellProcess;
use DennisDigital\Drupal\Console\Command\Drupal\Detector;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Utils\DrupalFinder;
use Drupal\Console\Bootstrap\Drupal;

/**
 * Class AbstractCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
abstract class AbstractContainerAwareCommand extends ContainerAwareCommand {
  protected $io = NULL;
  private $webRoot = NULL;
  private $root = NULL;
  /**
   * Constructor.
   */
  public function __construct(
    ConfigurationManager $configurationManager,
    $appRoot
  ) {
    $this->configurationManager = $configurationManager;
    $this->appRoot = $appRoot;
    parent::__construct();
  }
  protected function shellPath($file_name) {
    return addcslashes($this->cleanFileName($file_name), ' ');
  }
  protected function cleanFileName($file_name) {
    return str_replace('\ ', ' ', $file_name);
  }
  protected function getShellProcess() {
    return new ShellProcess($this->appRoot);
  }
  public function getDrupalVersion() {
    return 8;

  }

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

    $this->addOption(
      'site-url',
      '',
      InputOption::VALUE_REQUIRED,
      'The absolute url for this site.'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->validateSiteParams($input, $output);
  }

  protected function validateSiteParams(InputInterface $input, OutputInterface $output) {
    $this->io = new DrupalStyle($input, $output);
    $this->setWebRoot();
  }

  protected function setWebRoot() {
    $web_directory = empty($this->config['web_directory']) ? 'web' : $this->config['web_directory'];
    $this->webRoot = $this->getInstallDir() . trim($web_directory, '/') . '/';
    $this->webRoot = str_replace('//', '/', $this->webRoot);
  }
  protected function fileExists($file_name) {
    return file_exists($this->cleanFileName($file_name));
  }
  protected function getInstallDir() { return '/vagrant/repos/d8sandbox/';
    if (is_null($this->root)) {
      throw new CommandException('Installation directory is not available.');
    }

    if (isset($this->options['verbose'])) {
      $this->io->writeLn('Install dir: ' . $this->root);
    }

    return $this->root;
  }

  /**
   * Getter for the web root directory property.
   */
  protected function getWebRoot() {
    if (is_null($this->webRoot)) {
      throw new CommandException('Web root directory is not available.');
    }

    if (isset($this->options['verbose'])) {
      $this->io->writeLn('Web root: ' . $this->webRoot);
    }

    return $this->webRoot;
  }

  /**
   * Helper to check whether config files exist.
   *
   * @return string.
   */
  protected function getConfigUrl() {
    $shellProcess = $this->getShellProcess()->printOutput(FALSE);

    // Shell commands
    $command[] = sprintf('cd %s', $this->getWebRoot());
    //@todo
    $command[] = sprintf('drush eval "global \$config_directories; echo json_encode(\$config_directories);"');
    $command = implode(' && ', $command);

    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $autoload = $this->container->get('class_loader');
    $drupal = new Drupal(
      $autoload,
      $drupalFinder,
      $this->configurationManager
    );
    $container = $drupal->boot();
    $this->getApplication()->setContainer($container);
    $this->getApplication()->validateCommands();
    $this->getApplication()->loadCommands();

    if ($shellProcess->exec($command, TRUE)) {
      if ($conf = json_decode($shellProcess->getOutput())) {
        if ($this->configUrl = $conf->sync) {
          return $this->configUrl;
        }
      }
    }
  }

}
