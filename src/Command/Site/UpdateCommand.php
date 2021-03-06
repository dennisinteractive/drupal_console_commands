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
use Symfony\Component\Process\Exception\ProcessFailedException;

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

    $this->io->comment(sprintf('Running Updates on %s',
      $this->getWebRoot()
    ));

    $commands = [];
    $commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));

    // Drupal 7 only;
    if ($this->getDrupalVersion() === 7) {
      $commands[] = 'drush -q rr';
      $commands[] = 'drush vset maintenance_mode 1';
      $commands[] = 'drush updb -y';
      $this->addModuleEnableCommands($commands);
      $this->addModuleDisableCommands($commands);
      //$commands[] = 'drush rr';
      $commands[] = 'drush vset maintenance_mode 0';
    }

    // Drupal 8 only;
    if ($this->getDrupalVersion() === 8) {
      $commands[] = 'drush cim -y';
      $commands[] = 'drush cr';
      $commands[] = 'drush updb -y';
      $commands[] = 'drush cr';
      $this->addModuleEnableCommands($commands);
      $this->addModuleDisableCommands($commands);

      //$commands[] = 'drupal cache:rebuild all';
    }

    $command = implode(' && ', $commands);

    $this->io->commentBlock($command);

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      //$this->io->writeln($shellProcess->getOutput());
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }

  }

  /**
   * Enable modules listed on site.yml.
   */
  private function addModuleEnableCommands(&$commands) {
    if (!empty($this->config['modules']['enable'])) {

      // Drupal 7 only;
      if ($this->getDrupalVersion() === 7) {
        $commands[] = sprintf('drush en -y %s', implode(', ', $this->config['modules']['enable']));
      }

      // Drupal 8 only;
      if ($this->getDrupalVersion() === 8) {
        $commands[] = sprintf('drush en -y %s', implode(', ', $this->config['modules']['enable']));
      }
    }
  }

  /**
   * Disable modules listed on site.yml.
   */
  private function addModuleDisableCommands(&$commands) {
    if (!empty($this->config['modules']['disable'])) {

      // Drupal 7 only;
      if ($this->getDrupalVersion() === 7) {
        $commands[] = sprintf('drush pm-disable -y %s', implode(', ', $this->config['modules']['disable']));
        $commands[] = sprintf('drush pm-uninstall -y %s', implode(', ', $this->config['modules']['disable']));
      }

      // Drupal 8 only;
      if ($this->getDrupalVersion() === 8) {
        $commands[] = sprintf('drush pm-uninstall -y %s', implode(', ', $this->config['modules']['disable']));
      }
    }
  }

}

