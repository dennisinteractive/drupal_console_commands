<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\ComposeCommand.
 *
 * Runs composer installer.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class ComposeCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class ComposeCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:compose')
      // @todo use: ->setDescription($this->trans('commands.site.compose.description'))
      ->setDescription('Runs composer installer');
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

    if (!$this->fileExists($this->getInstallDir() . 'composer.json')) {
      $message = sprintf('The file composer.json is missing on %s',
        $this->getInstallDir()
      );
      throw new CommandException($message);
    }

    // Run composer install.
    $this->runCommand('install');
  }

  /**
   * Helper to run composer commands.
   *
   * @param $command The command to run.
   *
   * @return bool TRUE If successful.
   *
   * @throws CommandException
   */
  protected function runCommand($command) {
    $command = sprintf(
      'cd %s && composer %s',
      $this->shellPath($this->getInstallDir()),
      $command
    );
    $this->io->commentBlock($command);

    $shellProcess = $this->getShellProcess();

    //@todo Show a progress bar.
    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Composer installed on %s', $this->getInstallDir()));
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }

    return TRUE;
  }
}
