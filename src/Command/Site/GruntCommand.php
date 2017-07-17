<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\GruntCommand.
 *
 * Runs Grunt.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class GruntCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class GruntCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:grunt')
      ->setDescription('Runs Grunt.');
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

    $this->io->comment(sprintf('Running Grunt on %s',
      $this->web_root
    ));

    $command = sprintf(
      'cd %s && ' .
      'find . -type d \( -name node_modules -o -name contrib -o -path ./core \) -prune -o -name Gruntfile.js -execdir sh -c "grunt" \;',
      $this->shellPath($this->web_root)
    );

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success('Grunt job completed');
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }
  }

}
