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
 * Class SiteTestCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteUpdateCommand extends SiteBaseCommand {


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
      $this->destination
    ));

    $command = sprintf(
      'cd %sweb; drush site-set @site; drush sset system.maintenance_mode 1;
      drush cr; drush updb -y; drush sset system.maintenance_mode 0;
      drush cr;',
      $this->shellPath($this->destination)
    );

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success('Update Complete');
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());
    }
  }

}

