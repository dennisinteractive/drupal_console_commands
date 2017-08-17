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

    $commands = [];
    $commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));

    // Drupal 7 only;
    if ($this->getDrupalVersion() === 7) {
      $commands[] = 'drush vset maintenance_mode 1';
      $commands[] = 'drush rr';
      //$commands[] = 'drush cc all';
      $commands[] = 'drush updb -y';
      $commands[] = 'drush rr';
      //$commands[] = 'drush cc all';
      $commands[] = 'drush vset maintenance_mode 0';
    }

    // Drupal 8 only;
    if ($this->getDrupalVersion() === 8) {
      $commands[] = 'drush sset system.maintenance_mode 1';
      $commands[] = 'drush cr';
      $commands[] = 'drush updb -y';
      $commands[] = 'drush cim -y';
      $commands[] = 'drush cim -y';
      $commands[] = 'drush sset system.maintenance_mode 0';
      $commands[] = 'drush cr';
    }

    $command = implode(' ; ', $commands);

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

