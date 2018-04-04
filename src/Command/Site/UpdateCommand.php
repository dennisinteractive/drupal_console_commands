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

    $learning = $input->getOption('learning');

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
      $commands[] = 'drupal site:maintenance on';
      $commands[] = 'drupal module:update';
      $commands[] = 'drupal theme:update';
      $this->addModuleEnableCommands($commands);
      $this->addModuleDisableCommands($commands);
      if ($this->fileExists($this->getWebRoot() . $this->getConfigUrl() . '/system.site.yml')) {
        $commands[] = 'drupal config:import';
      }
      $commands[] = 'drupal site:maintenance  off';
      $commands[] = 'drupal cache:rebuild all';
    }

    $command = implode(' ; ', $commands);

    if ($learning) {
      $this->io->commentBlock($command);
    }

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

  /**
   * Enable modules listed on site.yml.
   */
  private function addModuleEnableCommands(&$commands) {
    if (!empty($this->config['modules']['enable'])) {

      // Drupal 7 only;
      if ($this->getDrupalVersion() === 7) {
        $commands[] = sprintf('drupal module:install %s', implode(', ', $this->config['modules']['enable']));
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
        $commands[] = sprintf('drupal module:uninstall %s', implode(', ', $this->config['modules']['disable']));
      }
    }
  }

}

