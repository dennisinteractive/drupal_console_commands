<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteGruntCommand.
 *
 * Runs Grunt.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteGruntCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteGruntCommand extends SiteBaseCommand {

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
      $this->destination
    ));

    $command = sprintf(
      'cd %sweb && ' .
      'find . -type d \( -name node_modules -o -name contrib -o -path ./core \) -prune -o -name Gruntfile.js -execdir sh -c "grunt" \;',
      $this->shellPath($this->destination)
    );

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success('Grunt job completed');
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());
    }
  }

}
