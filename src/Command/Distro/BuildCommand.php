<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Distro\BuildCommand.
 *
 * Builds the distro by calling various commands.
 */

namespace DennisDigital\Drupal\Console\Command\Distro;

use DennisDigital\Drupal\Console\Command\Site\AbstractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class DistroBuildCommand
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
      'distro:checkout',
      'distro:compose|make',
      'distro:npm',
      'distro:grunt',
      'distro:settings',
      'distro:phpunit:setup',
      'distro:behat:setup',
      'distro:db:import',
      'distro:update',
    );
    $this->setName('distro:build')
      ->setDescription(sprintf('Runs the following commands to build a distro: %s.', implode(', ', $commands)));

    // Custom options.
    $this->addOption(
      'branch',
      '-B',
      InputOption::VALUE_OPTIONAL,
      'Specify which branch to build'
    );
    $this->addOption(
      'skip',
      '',
      InputOption::VALUE_OPTIONAL,
      'Used to skip one or more commands. i.e. --skip="checkout"'
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
      'command' => 'distro:checkout',
      'arguments' => array(
        'name' => $this->distroName,
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
      $command = 'distro:compose';
    }
    elseif (file_exists($this->getRoot() . '/distro.make')) {
      if (in_array('make', $this->skip)) {
        return;
      }
      $command = 'distro:make';
    }
    else {
      throw new CommandException(sprintf('Could not find composer.json or distro.make in %s', $this->getRoot()));
    }

    $this->commands[] = array(
      'command' => $command,
      'arguments' => array(
        'name' => $this->distroName,
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
      'command' => 'distro:npm',
      'arguments' => array(
        'name' => $this->distroName,
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
      'command' => 'distro:grunt',
      'arguments' => array(
        'name' => $this->distroName,
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
      'command' => 'distro:settings',
      'arguments' => array(
        'name' => $this->distroName,
      )
    );
  }

  /**
   * Tests setup command.
   */
  private function addTestSetupCommand() {
    if (!in_array('phpunit:setup', $this->skip)) {
      $this->commands[] = array(
        'command' => 'distro:phpunit:setup',
        'arguments' => array(
          'name' => $this->distroName,
        )
      );
    }
    if (!in_array('behat:setup', $this->skip)) {
      $this->commands[] = array(
        'command' => 'distro:behat:setup',
        'arguments' => array(
          'name' => $this->distroName,
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
      'command' => 'distro:db:import',
      'arguments' => array(
        'name' => $this->distroName,
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
      'command' => 'distro:update',
      'arguments' => array(
        'name' => $this->distroName,
      )
    );
  }

}
