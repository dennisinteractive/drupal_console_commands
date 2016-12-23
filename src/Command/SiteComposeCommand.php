<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteComposeCommand.
 *
 * Runs composer installer.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteComposeCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteComposeCommand extends SiteBaseCommand {

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

    if (!file_exists($this->destination . 'composer.json')) {
      $message = sprintf('The file composer.json is missing on %s',
        $this->destination
      );
      throw new SiteCommandException($message);
    }

    // Run composer install.
    $this->runCommand('install', $this->destination);
  }

  /**
   * Helper to run composer commands.
   *
   * @param $command The command to run.
   * @param $destination The destination folder.
   *
   * @return bool TRUE If successful.
   *
   * @throws SiteCommandException
   */
  protected function runCommand($command, $destination) {
    $command = sprintf(
      'cd %s; composer %s;',
      $destination,
      $command
    );
    $this->io->commentBlock($command);

    $shellProcess = $this->shellProcess;

    //@todo Show a progress bar.
    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Composer installed on %s', $this->destination));
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());
    }

    return TRUE;
  }
}
