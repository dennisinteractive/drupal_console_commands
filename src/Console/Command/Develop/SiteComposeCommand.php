<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteComposeCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VM\Console\Command\Exception\SiteCommandException;

/**
 * Class SiteComposeCommand
 *
 * @package VM\Console\Command\Develop
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
    $this->composerInstall($this->destination);
  }

  /**
   * Helper to run composer install.
   *
   * @param $destination The destination folder.
   *
   * @return bool TRUE If successful.
   *
   * @throws SiteCommandException
   */
  protected function composerInstall($destination) {
    $command = sprintf('cd %s; composer -v install', $destination);
    $this->io->commentBlock($command);

    $shellProcess = $this->get('shell_process');

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
