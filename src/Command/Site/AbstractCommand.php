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

/**
 * Class AbstractCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
abstract class AbstractCommand extends Command {

  /**
   * @var configurationManager
   */
  protected $configurationManager;

  /**
   * IO interface.
   *
   * @var null
   */
  protected $io = NULL;

  /**
   * Stores the options passed to the command via command line.
   */
  protected $options;

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
  private $root = NULL;

  /**
   * The web root directory.
   *
   * This is the web directory within the root.
   *
   * @var string
   */
  private $webRoot = NULL;

  /**
   * The site root directory.
   *
   * This is where we put settings.php
   *
   * @var string
   */
  private $siteRoot = NULL;

  /**
   * Stores the site url.
   *
   * @var string
   */
  protected $url = NULL;

  /**
   * Stores the app root.
   */
  protected $appRoot;

  /**
   * Stores the drupal core version.
   */
  protected $drupalVersion = NULL;

  /**
   * Stores the environment i.e. dev
   */
  protected $env = NULL;

  /**
   * Stores the url to the config files
   *
   * @var string
   */
  protected $configUrl = NULL;

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

  /**
   * @param mixed $env
   */
  public function setEnv($env) {
    $this->env = $env;
  }

  /**
   * @return mixed
   */
  public function getEnv() {
    if (is_null($this->env)) {
      $this->detectEnv();
    }
    return $this->env;
  }

  /**
   * Figures out the env.
   *
   * @param null $input
   */
  protected function detectEnv($input = NULL) {
    // Get Console global settings.
    $configEnv = $this->configurationManager->getConfiguration()->get('application.environment');

    // Detect env.
    if (isset($this->options['env'])) {
      // Use value passed as parameter --env.
      $this->setEnv($this->options['env']);
    }
    elseif (!empty($configEnv)) {
      // Use config from sites.yml.
      $this->setEnv($configEnv);
    }
    else {
      // Default to dev.
      $this->setEnv('dev');
    }
  }

  /**
   * @return mixed
   */
  public function getDrupalVersion() {
    if (!is_numeric($this->drupalVersion)) {
      $detector = new Detector();

      $webRoot = $this->getWebRoot();
      $version = $detector->getDrupalVersion($webRoot);
      if (is_numeric($version)) {
        $this->setDrupalVersion($version);
        if (isset($this->options['verbose'])) {
          $this->io->writeln(sprintf('Drupal %s detected.', $version));
        }
      }
    }

    return $this->drupalVersion;
  }

  /**
   * @param mixed $drupalVersion
   */
  public function setDrupalVersion($drupalVersion) {
    $this->drupalVersion = $drupalVersion;
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

    if ($input->hasOption('env') && !is_null($input->getOption('env'))) {
      $this->options['env'] = $input->getOption('env');
    }

    if ($output->isVerbose()) {
      $this->options['verbose'] = TRUE;
    }

    // Sites list filtered by environment.
    $options = $this->siteList();
    if (empty($options)) {
      throw new CommandException(sprintf('No sites available for %s environment.', $this->getEnv()));
    }

    // Detect name.
    $name = $input->getArgument('name');
    if (!$name) {
      $name = $this->io->choice(
        $this->trans(sprintf('Select %s a site', $this->getEnv())),
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

    // Fix folder permissions.
    $this->fixSiteFolderPermissions();

    // Get config.
    $this->siteConfig($input);

    // Validate profile.
    $this->setProfile($input);

    // Set installation directory.
    $this->setInstallDir($input);

    // Set web root.
    $this->setWebRoot();

    // Set site root.
    $this->setSiteRoot();

    // Validate url.
    $this->setUrl($input);
  }

  /**
   * Getter for the root directory property.
   */
  protected function getInstallDir() {
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
   * Getter for the site root directory property.
   */
  protected function getSiteRoot() {
    if (is_null($this->siteRoot)) {
      throw new CommandException(sprintf('Site root directory is not available in %s', $this->siteName));
    }

    if (isset($this->options['verbose'])) {
      $this->io->writeLn('Site root: ' . $this->siteRoot);
    }

    return $this->siteRoot;
  }

  /**
   * Check if the current build has a site root directory.
   *
   * @return bool
   */
  protected function hasSiteRoot() {
    return !is_null($this->siteRoot);
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

    // Support for site name without env or with env.
    $config = $this->configurationManager->readTarget($siteName);
    if (empty($config))
    {
      $config = $this->configurationManager->readTarget($siteName . '.' . $this->getEnv());
    }
    if (empty($config))
    {
      $message = sprintf(
        'Site %s not found on %s env. To see a list of available sites, run drupal debug:site',
        $siteName,
        $this->getEnv()
      );
      throw new CommandException($message);
    }

    // Update input.
    $input->setArgument('name', $siteName);
    $this->siteName = $siteName;
    $this->config = $config[$this->getEnv()];

    return $this;
  }

  /**
   * Helper to validate profile parameter.
   *
   * @param InputInterface $input
   *
   * @return string Profile
   */
  protected function setProfile(InputInterface $input) {
    if ($input->hasArgument('profile') &&
      !is_null($input->getArgument('profile'))
    ) {
      // Use config from parameter.
      $this->profile = $input->getArgument('profile');
    }

    return $this->profile;
  }

  /**
   * Validate and set the web root directory.
   * This is the place were we run commands from.
   */
  protected function setWebRoot() {
    $web_directory = empty($this->config['web_directory']) ? 'web' : $this->config['web_directory'];
    $this->webRoot = $this->getInstallDir() . trim($web_directory, '/') . '/';
    $this->webRoot = str_replace('//', '/', $this->webRoot);
  }

  /**
   * Validate and set the installation directory, where the website was checked out.
   *
   * @param InputInterface $input
   * @return string
   */
  protected function setInstallDir(InputInterface $input) {
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
   * Validate URL.
   *
   * @param InputInterface $input
   * @return string
   */
  protected function setUrl(InputInterface $input) {
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
   * Set the site root.
   *
   * This is where we place settings.php
   *
   * It will try to match a folder with same name as site name
   * If not found, it will try to match a folder called "default".
   *
   * @return string Path
   */
  public function setSiteRoot() {
    if (!is_null($this->siteRoot)) {
      return $this->siteRoot;
    }

    // Support for sites that live in docroot/sites/sitename.
    if (isset($this->config['web_directory']) && $this->config['web_directory'] == '/') {
      $webSitesPath = $this->getWebRoot();
      $settingsPath = $webSitesPath;
    }
    else {
      // Support for sites that live in docroot/web.
      $webSitesPath = $this->getWebRoot() . 'sites/';
      $settingsPath = $webSitesPath . 'default';
    }

    // It's possible that a command is run before the site is available. e.g. checkout
    // We will skip setting in this situation, but throw an Exception in the site root getter to prevent any unpredictable behaviour.
    if (!file_exists($settingsPath)) {
      return;
    }

    $command = sprintf(
      'cd %s && find . -name settings.php',
      $this->shellPath($webSitesPath)
    );

    //$this->io->writeln('Searching for settings.php in the sites folder');
    $shellProcess = $this->getShellProcess()->printOutput(FALSE);
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

    // Fix folder permissions.
    $this->fixSiteFolderPermissions();
    $this->siteRoot = $settingsPath;
  }

  /**
   * Fixes the site folder permissions which is often changed by Drupal core.
   */
  protected function fixSiteFolderPermissions() {
    if ($this->hasSiteRoot()) {
      $commands = array();

      $items = array(
        $this->getSiteRoot(),
        $this->getSiteRoot() . 'settings.php'
      );

      foreach ($items as $key => $item) {
        // Only change permissions if needed.
        if (file_exists($item)) {
          if (substr(sprintf('%o', fileperms($item)), -4) != '0777') {
            $commands[] = sprintf('chmod 0777 %s', $item);
          }
        }
      }

      if (empty($commands)) {
        return;
      }

      $command = implode(' && ', $commands);

      if (isset($this->options['verbose'])) {
        $this->io->commentBlock($command);
      }

      $shellProcess = $this->getShellProcess()->printOutput(FALSE);
      if (!$shellProcess->exec($command, TRUE)) {
        throw new CommandException($shellProcess->getOutput());
      }
    }
  }

  /**
   * Get the shell process.
   *
   * @return ShellProcess
   */
  protected function getShellProcess() {
    return new ShellProcess($this->appRoot);
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
   * This function lists all sites, filtering by the environment (-e)
   *
   * @return array List
   */
  private function siteList() {
    $sites = array_keys($this->configurationManager->getSites());
    $siteList = array();

    foreach ($sites as $site) {
      $config = $this->configurationManager->readTarget($site);

      // Filter by option --env.
      if (isset($config[$this->getEnv()])) {
        $siteList[] = $config[$this->getEnv()]['name'];
      }
    }

    return array_unique($siteList);
  }

  /**
   * Loads template.
   *
   * @return String The contents of the template.
   */
  function loadTemplate($file, $templateName) {
    if (!is_numeric($this->getDrupalVersion())) {
      throw new CommandException(sprintf('Unable to detect Drupal core version'));
    }

    $template =  realpath(dirname($file)) . '/Includes/Drupal' . $this->getDrupalVersion() . '/' . $templateName;

    return file_get_contents($template);
  }

}
