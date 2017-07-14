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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;
use DennisDigital\Drupal\Console\Utils\ShellProcess;

/**
 * Class AbstractCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
abstract class AbstractCommand extends Command {

  use CommandTrait;

  /**
   * @var ConfigurationManager
   */
  protected $configurationManager;

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
   * The root directory.
   *
   * @var string
   */
  protected $root = NULL;

  /**
   * The web root directory.
   *
   * This is the web directory within the root.
   *
   * @var string
   */
  protected $web_root = NULL;

  /**
   * Stores the site url.
   *
   * @var string
   */
  protected $url = NULL;

  /**
   * Stores the container.
   */
  protected $container;

  /**
   * Constructor.
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * @param mixed $container
   */
  public function setContainer($container)
  {
    $this->container = $container;
    $this->configurationManager = $this->container
      ->get('console.configuration_manager');
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
  protected function interact(InputInterface $input, OutputInterface $output) {
    $this->io = new DrupalStyle($input, $output);

    $sitesDirectory = $this->configurationManager->getSitesDirectory();
    $options = $this->siteList($sitesDirectory);

    $name = $input->getArgument('name');
    if (!$name) {
      $name = $this->io->choice(
        $this->trans('Select a site'),
        $options,
        reset($options),
        TRUE
      );
      $input->setArgument('name', reset($name));
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
   * Helper to validate parameters.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  protected function validateSiteParams(InputInterface $input, OutputInterface $output) {
    $this->io = new DrupalStyle($input, $output);

    // Get config.
    $this->siteConfig($input);

    // Validate profile.
    $this->validateProfile($input);

    // Validate root.
    $this->validateRoot($input);

    // Validate web root.
    $this->validateWebRoot();

    // Validate url.
    $this->validateUrl($input);
  }

  /**
   * Helper to check that the config file exits and load the configuration.
   *
   * @param InputInterface $input
   *
   * @return $this
   *
   * @throws CommandException
   */
  protected function siteConfig(InputInterface $input) {
    $siteName = $input->getArgument('name');

    // $environment = $input->getOption('env')
    $environment = $this->configurationManager->getConfiguration()
      ->get('application.environment');

    $config = $this->configurationManager->readTarget($siteName . '.' . $environment);

    if (empty($config))
    {
      $message = sprintf(
        'Site not found. To see a list of available sites, run %s',
        'drupal site:debug'
      );
      throw new CommandException($message);
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
  protected function validateProfile(InputInterface $input) {
    if ($input->hasArgument('profile') &&
      !is_null($input->getArgument('profile'))
    ) {
      // Use config from parameter.
      $this->profile = $input->getArgument('profile');
    }
    elseif (isset($this->config['profile'])) {
      // Use config from ~/.console/sites/{site}.yml.
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
   * Validate and set the web root directory.
   */
  protected function validateWebRoot() {
    $this->web_root = $this->root . trim($this->config['web_directory'], '/') . '/';
  }

  /**
   * Validate and set the root directory.
   *
   * @param InputInterface $input
   * @return string
   */
  protected function validateRoot(InputInterface $input) {
    if ($input->hasOption('destination-directory') &&
      !is_null($input->getOption('destination-directory'))
    ) {
      // Use config from parameter.
      $this->root = $input->getOption('destination-directory');
    }
    elseif (isset($this->config['root'])) {
      // Use config from sites.yml.
      $this->root = $this->config['root'];
    }
    else {
      $this->root = '/tmp/' . $this->siteName;
    }

    // Allow destination to be overriden by environment variable. i.e.
    // export site_destination_directory="/directory/"
    if (!getenv('site_destination_directory')) {
      putenv("site_destination_directory=$this->root");
    }
    else {
      $this->root = getenv('site_destination_directory');
    }

    $this->root = rtrim($this->root, '/') . '/';
  }

  /**
   * Helper to validate URL.
   *
   * @param InputInterface $input
   * @return string
   */
  protected function validateUrl(InputInterface $input) {
    $scheme = isset($this->config['scheme']) && !empty($this->config['scheme']) ? $this->config['scheme'] : 'http';

    if (isset($this->config['host']) && !empty($this->config['host'])) {
      $host = $this->config['host'];
      $url = "{$scheme}://{$host}";
    }

    if ($url && filter_var($url, FILTER_VALIDATE_URL) !== FALSE) {
      $this->url = $url;
    };

    return $this->url;
  }

  /**
   * Helper to return the path to settings.php
   * It will try to match a folder with same name as site name
   * If not found, it will try to match a folder called "default".
   *
   * @return string Path
   */
  public function settingsPhpDirectory() {
    $webSitesPath = $this->web_root . 'sites/';
    $settingsPath = $webSitesPath . 'default';

    $command = sprintf(
      'cd %s && find . -name settings.php',
      $this->shellPath($webSitesPath)
    );

    $this->io->writeln('Searching for settings.php in the sites folder');
    $shellProcess = $this->getShellProcess();
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

  /**
   * Get the shell process.
   *
   * @return ShellProcess
   */
  protected function getShellProcess() {
    $app_root = $this->container->get('app.root');
    return new ShellProcess($app_root);
  }

  /**
   * Check if a file exists.
   *
   * @param $file_name
   * @return bool
   */
  protected function fileExists($file_name) {
    return file_exists($this->cleanFileName($file_name));
  }

  /**
   * Write contents to specified file.
   *
   * @param $file_name
   * @param $contents
   * @return int
   */
  protected function filePutContents($file_name, $contents) {
    // Ensure the folder exists.
    if (!file_exists(dirname($file_name)) || is_writable(dirname($file_name)) !== TRUE) {
      mkdir(dirname($file_name), 0664, TRUE);
    }
    return file_put_contents($this->cleanFileName($file_name), $contents);
  }

  /**
   * Get contents of specified file.
   *
   * @param $file_name
   * @return string
   */
  protected function fileGetContents($file_name) {
    return file_get_contents($this->cleanFileName($file_name));
  }

  /**
   * Remove specified file.
   *
   * @param $file_name
   * @return bool
   */
  protected function fileUnlink($file_name) {
    return unlink($this->cleanFileName($file_name));
  }

  /*
   * Clean the provided file_name.
   * - Removes any escaped spaces for use with PHP file functions.
   *
   * @param $file_name
   * @return string
   */
  protected function cleanFileName($file_name) {
    return str_replace('\ ', ' ', $file_name);
  }

  /*
   * Prepare file names for shell commands.
   * - Escapes spaces with backslashes
   *
   * @param $file_name
   * @return string
   */
  protected function shellPath($file_name) {
    return addcslashes($this->cleanFileName($file_name), ' ');
  }

  /**
   * Extracted from DebugCommand.
   * This function lists all sites found in ~/.console/sites folder
   * It will only return sites that have 'repo' configured
   *
   * @param string $sitesDirectory
   *
   * @return array
   */
  private function siteList($sitesDirectory) {
    $finder = new Finder();
    $finder->in($sitesDirectory);
    $finder->name("*.yml");

    $tableRows = [];
    foreach ($finder as $site) {
      $siteName = $site->getBasename('.yml');
      $environments = $this->configurationManager
        ->readSite($site->getRealPath());

      if (!$environments || !is_array($environments)) {
        continue;
      }

      foreach ($environments as $environment => $config) {
        if (isset($config['repo'])) {
          $tableRows[] = $siteName;
        }
      }
    }

    return $tableRows;
  }

}
