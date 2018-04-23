<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\BuildCommand.
 *
 * Builds the site by calling various commands.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class SiteBuildCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class BuildCommand extends AbstractCommand {

  /**
   * Stores branch information.
   *
   * @var array Branch.
   */
  protected $branch;

  /**
   * @var commands.
   */
  private $commands;

  /**
   * Stores the Input.
   */
  private $input;

  /**
   * Stores the Output.
   */
  private $output;

  /**
   * Stores global options passed.
   */
  private $inputOptions;

  /**
   * Stores the commands to be skipped.
   */
  private $skip;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $commands = array(
      'site:checkout',
      'site:compose|make',
      'site:npm',
      'site:grunt',
      'site:settings',
      'site:phpunit:setup',
      'site:behat:setup',
      'site:db:import',
      'site:update',
    );
    $this->setName('site:build')
      ->setDescription(sprintf('Runs the following commands to build a site: %s.', implode(', ', $commands)));

    // Custom options.
    $this->addOption(
      'branch',
      '-B',
      InputOption::VALUE_OPTIONAL,
      'Specify which branch to build if different than the global branch found in sites.yml'
    );
    $this->addOption(
      'profile',
      '',
      InputOption::VALUE_NONE,
      'Which profile to use. i.e. standard'
    );
    $this->addOption(
      'skip',
      '',
      InputOption::VALUE_OPTIONAL,
      'Used to skip one or more commands. i.e. --skip="checkout, phpunit:setup"'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    $this->input = $input;
    $this->output = $output;
    $this->inputOptions = array_filter($input->getOptions());

    $this->skip = array();
    if (isset($this->inputOptions['skip'])) {
      $this->skip = explode(',', $this->inputOptions['skip']);
    }
    // Trim input.
    $this->skip = array_map('trim', $this->skip);

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    $this->commands = array();

    // Run checkout first because Compose/Make depends on it.
    $this->addCheckoutCommand();
    $this->runList();

    $this->setSiteRoot();

    $profile = $input->getOption('profile');
    // Try to get the profile from settings.php.
    if (!$profile) {
      $settingsPhp = $this->getSiteRoot() . 'settings.php';
      if (file_exists($settingsPhp)) {
        $regex = '|\$settings\[\'install_profile\'\] = \'(\w+)\'|';
        $contents = file_get_contents($settingsPhp);
        preg_match($regex, $contents, $matches);
        if (!empty($matches)) {
          $profile = $matches[1];
        }
      }
    }
    // Ask for user input.
    if (!$profile) {
      $profile = $this->getIo()->ask(
        $this->trans('Which profile would you like to install?'),
        'standard'
      );
    }
    $this->profile = $profile;
    $input->setOption('profile', $profile);

    // Run compose now because we need to have core to set the site root.
    $this->addComposeMakeCommand();
    $this->runList();

    // Add entry on sites.php.
    $this->addSitesPhp();

    // Queue other commands.
    $this->addNPMCommand();
    $this->addGruntCommand();
    $this->addSettingsCommand();
    $this->addTestSetupCommand();
    $this->addDbImportCommand();
    $this->addUpdateCommand();
    $this->runList();

    $this->io->success(sprintf(
      "Site url %s\nURL %s",
      $this->getSiteRoot(),
      $this->config['host']
    ));
  }

  /**
   * Runs the commands.
   */
  private function runList() {
    foreach ($this->commands as $item) {
      $parameters = array();
      $command = $this->getApplication()->find($item['command']);

      // Command arguments.
      if (!empty($item['arguments'])) {
        foreach ($item['arguments'] as $name => $value) {
          $parameters[$name] = $value;
        }
      }

      // Command options.
      if (isset($item['options'])) {
        $options = array_filter($item['options']);
        foreach ($options as $name => $value) {
          $parameters['--' . $name] = $value;
        }
      }

      // Append env if needed.
      if (isset($this->inputOptions['env'])) {
        $parameters['--env'] = $this->inputOptions['env'];
      }

      $this->io->writeln(sprintf('// %s', $item['command']));

      $commandInput = new ArrayInput(array_filter($parameters));
      $command->run($commandInput, $this->output);
    }

    $this->commands = array();
  }

  /**
   * Add the site host to sites.php.
   */
  private function addSitesPhp() {
    // Get site root from config.
    $siteRoot = $this->getSiteRoot();

    // Get the site dir (last part of the docroot)
    $siteRootArray = explode('/', $siteRoot);
    $siteRootArray = array_filter($siteRootArray);
    $siteDir = array_pop($siteRootArray);

    // Get sites folder (one before the last)
    $sitesFolder = '/' . implode('/', $siteRootArray) . '/';

    // Path for sites.php/
    $sitesPHP = $sitesFolder . 'sites.php';

    // Check if sites.php exists otherwise try to copy from example.sites.php.
    if (!file_exists($sitesPHP)) {
      $defaultSitesPHP = $sitesFolder . 'example.sites.php';
      if (!file_exists($defaultSitesPHP)) {
        $this->io->error("Could not find sites.php or example.sites.php on $sitesFolder");
        return;
      }
      // Copy the file.
      copy($defaultSitesPHP, $sitesPHP);
    }

    // Append the host if necessary.
    $contents = file_get_contents($sitesPHP);

    // Generate site config entry.
    $config = sprintf(PHP_EOL . '$sites[\'%s\'] = \'%s\';' . PHP_EOL,
      $this->config['host'],
      $siteDir
    );

    // Check if config needs to be appended.
    if (strpos($contents, $config) === FALSE) {
      file_put_contents($sitesPHP, $contents . $config);
      $this->io->comment('Added ' . $this->config['host'] . ' to sites.php');
    }
  }

  /**
   * Checkout command.
   */
  private function addCheckoutCommand() {
    if (in_array('checkout', $this->skip)) {
      return;
    }

    $this->commands[] = array(
      'command' => 'site:checkout',
      'arguments' => array(
        'name' => $this->siteName,
      ),
      'options' => array(
        'branch' => ($this->input->hasOption('branch')) ? $this->input->getOption('branch') : NULL,
      ),
    );
  }

  /**
   * Composer or make command.
   */
  private function addComposeMakeCommand() {
    if (file_exists($this->getInstallDir() . '/composer.json')) {
      if (in_array('compose', $this->skip)) {
        return;
      }
      $command = 'site:compose';
    }
    elseif (file_exists($this->getInstallDir() . '/site.make')) {
      if (in_array('make', $this->skip)) {
        return;
      }
      $command = 'site:make';
    }
    else {
      throw new CommandException(sprintf('Could not find composer.json or site.make in %s', $this->getInstallDir()));
    }

    $this->commands[] = array(
      'command' => $command,
      'arguments' => array(
        'name' => $this->siteName,
      )
    );
  }

  /**
   * NPM command.
   */
  private function addNPMCommand() {
    if (in_array('npm', $this->skip)) {
      return;
    }

    $this->commands[] = array(
      'command' => 'site:npm',
      'arguments' => array(
        'name' => $this->siteName,
      )
    );
  }

  /**
   * Grunt command.
   */
  private function addGruntCommand() {
    if (in_array('grunt', $this->skip)) {
      return;
    }

    $this->commands[] = array(
      'command' => 'site:grunt',
      'arguments' => array(
        'name' => $this->siteName,
      )
    );
  }

  /**
   * Settings command.
   */
  private function addSettingsCommand() {
    if (in_array('settings', $this->skip)) {
      return;
    }

    $this->commands[] = array(
      'command' => 'site:settings',
      'arguments' => array(
        'name' => $this->siteName,
      )
    );
  }

  /**
   * Tests setup command.
   */
  private function addTestSetupCommand() {
    if (!in_array('phpunit:setup', $this->skip)) {
      $this->commands[] = array(
        'command' => 'site:phpunit:setup',
        'arguments' => array(
          'name' => $this->siteName,
        )
      );
    }
    if (!in_array('behat:setup', $this->skip)) {
      $this->commands[] = array(
        'command' => 'site:behat:setup',
        'arguments' => array(
          'name' => $this->siteName,
        )
      );
    }
  }

  /**
   * DB Import command.
   */
  private function addDbImportCommand() {
    if (in_array('db:import', $this->skip)) {
      return;
    }

    $this->commands[] = array(
      'command' => 'site:db:import',
      'arguments' => array(
        'name' => $this->siteName,
        'profile' => $this->profile,
      )
    );
  }

  /**
   * Update command.
   */
  private function addUpdateCommand() {
    if (in_array('update', $this->skip)) {
      return;
    }

    $this->commands[] = array(
      'command' => 'site:update',
      'arguments' => array(
        'name' => $this->siteName,
      )
    );
  }

}
