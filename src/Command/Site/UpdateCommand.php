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
 * Class TestCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class UpdateCommand extends AbstractCommand {


  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:update')
      ->setDescription('Update.');

  }

  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);


    $this->io->comment(sprintf('Running Update on %s',
      $this->getWebRoot()
    ));

    $commands = [
      sprintf('cd %s', $this->shellPath($this->webRoot)),
      'drush sset system.maintenance_mode 1',
      'drush cr',
      'drush updb -y',
      'drush cim -y',
      'drush cim -y',
      'drush sset system.maintenance_mode 0',
      'drush cr',
    ];
    $command = implode(' && ', $commands);

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success('Update Complete');
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }
  }

}

