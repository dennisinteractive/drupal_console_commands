<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\BuildCommand.
 *
 * Builds the site by calling various commands.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

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
   * @var ShellProcess
   */
  private $process;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:build')
      ->setDescription('Build a site');

    // Custom options.
    $this->addOption(
        'branch',
        '-B',
        InputOption::VALUE_OPTIONAL,
        'Specify which branch to build if different than the global branch found in sites.yml'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    $commands = array(
      sprintf('drupal site:checkout %s', $this->siteName),
      $this->getComposerMakeCommand(),
      'drupal site:npm %s',
      'drupal site:grunt %s',
      'drupal site:settings %s',
      'drupal site:phpunit:setup %s',
      'drupal site:test:setup %s',
      'drupal site:db:import %s',
      'drupal site:update %s',
    );

    foreach ($commands as $commandName) {
      $command = $this->getApplication()->find($commandName);

      $parameters = $input->getArguments();
      foreach ($input->getOptions() as $name => $value) {
        $parameters['--' . $name] = $value;
      }
      print_r($parameters);
      $commandInput = new ArrayInput(array_filter($parameters));

      $command->run($commandInput, $output);
    }
  }

  /**
   * Detect if the site uses composer or make files.
   */
  private function getComposerMakeCommand() {
   if (file_exists($this->getWebRoot() . '/compsoser.json')) {
      $composerMakeCommand = 'drupal site:compose %s';
    }
    else if (file_exists($this->getWebRoot() . '/site.make')) {
      $composerMakeCommand = 'drupal site:compose %s';
    }
    else if (file_exists($this->getWebRoot() . sprintf('/%s.make', $this->siteName))) {
      $composerMakeCommand = 'drupal site:compose %s';
    }
    else {
      $composerMakeCommand = '';
    }

    return $composerMakeCommand;
  }

}
