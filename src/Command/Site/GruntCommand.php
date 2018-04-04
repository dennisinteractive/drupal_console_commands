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

    $learning = $input->getOption('learning');

    $this->io->comment(sprintf('Running Grunt on %s',
      $this->getWebRoot()
    ));

    $commands = [];
    $commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));
    $commands[] = sprintf('find . -type d \( -name node_modules -o -name vendor -o -name contrib -o -path ./core \) -prune -o -name Gruntfile.js -execdir sh -c "pwd; npm; grunt" \;',
      $this->shellPath($this->getWebRoot())
    );
    $command = implode(' && ', $commands);

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($learning) {
      $this->io->commentBlock($command);
    }

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success('Grunt job completed');
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }
  }

}
