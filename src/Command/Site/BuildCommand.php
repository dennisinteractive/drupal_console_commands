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
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;
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
    //@todo populate other commands artuments here.
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
      'site:checkout' => sprintf('%s', $this->siteName),
      $this->getComposerMakeCommand() => sprintf('%s', $this->siteName),
      'site:npm' => sprintf('%s', $this->siteName),
      'site:grunt' => sprintf('%s', $this->siteName),
      'site:settings' => sprintf('%s', $this->siteName),
      'site:phpunit:setup' => sprintf('%s', $this->siteName),
      'site:test:setup' => sprintf('%s', $this->siteName),
      'site:db:import' => sprintf('%s', $this->siteName),
      'site:update' => sprintf('%s', $this->siteName),
    );

    foreach ($commands as $commandName => $args) {
      $command = $this->getApplication()->find($commandName);

      $parameters = $input->getArguments();
      foreach ($input->getOptions() as $name => $value) {
        $parameters['--' . $name] = $value;
      }
      $commandInput = new ArrayInput(array_filter($parameters));

      $command->run($commandInput, $output);
    }
  }

  /**
   * Detect if the site uses composer or make files.
   */
  private function getComposerMakeCommand() {
   if (file_exists($this->getRoot() . '/composer.json')) {
      return 'site:compose';
    }
    else if (file_exists($this->getRoot() . '/site.make')) {
      return 'site:make';
    }
    throw new CommandException(sprintf('Could not find composer.json or site.make in %s', $this->getRoot()));
  }

}
