<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\BuildCommand.
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
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    $this->commands = array();

    $this->addCheckoutCommand();
    // Run checkout first because Compose/Make depends on it.
    $this->runList();

    $this->addComposeMakeCommand();
    $this->addNPMCommand();
    $this->addGruntCommand();
    $this->addSettingsCommand();
    $this->addTestSetupCommand();
    $this->addDbImportCommand();
    $this->addUpdateCommand();
    $this->runList();
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
      //echo var_export($parameters, true) . PHP_EOL;

      $commandInput = new ArrayInput(array_filter($parameters));
      $command->run($commandInput, $this->output);
    }

    $this->commands = array();
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
    if (file_exists($this->getRoot() . '/composer.json')) {
      if (in_array('compose', $this->skip)) {
        return;
      }
      $command = 'site:compose';
    }
    elseif (file_exists($this->getRoot() . '/site.make')) {
      if (in_array('make', $this->skip)) {
        return;
      }
      $command = 'site:make';
    }
    else {
      throw new CommandException(sprintf('Could not find composer.json or site.make in %s', $this->getRoot()));
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
