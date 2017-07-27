<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\MakeCommand.
 *
 * Runs site make.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class MakeCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class MakeCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:make')
      ->setDescription('Runs site make');
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

    if (!$this->fileExists($this->getRoot() . 'site.make')) {
      $message = sprintf('The file sote.make is missing on %s',
        $this->getRoot()
      );
      throw new CommandException($message);
    }

    // Run drush command.
    $this->runCommand('make -y --working-copy --no-core --contrib-destination=. site.make');
  }

  /**
   * Helper to run drush commands.
   *
   * @param $command The command to run.
   *
   * @return bool TRUE If successful.
   *
   * @throws CommandException
   */
  protected function runCommand($command) {
    $command = sprintf(
      'cd %s && drush %s',
      $this->shellPath($this->getRoot()),
      $command
    );
    $this->io->commentBlock($command);

    $shellProcess = $this->getShellProcess();

    //@todo Show a progress bar.
    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Site built on %s', $this->getRoot()));
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }

    return TRUE;
  }
}
