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
      $commands[] = 'drupal update:execute';
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

    // We run config:import separately so that if there's no config and it
    // fails we can continue. Previously, we checked the config folder, but this
    // was a quick fix.
    if ($this->getDrupalVersion() === 8) {
      $config_commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));
      $config_commands[] = 'drupal config:import';
      $config_command = implode(' && ', $config_commands);

      $this->io->commentBlock($config_command);

      try {
        $shellProcess->exec($config_command, TRUE);
      }
      catch (ProcessFailedException $e) {
      }
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
        $commands[] = sprintf('drupal module:install %s', implode(', ', $this->config['modules']['enable']));
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

