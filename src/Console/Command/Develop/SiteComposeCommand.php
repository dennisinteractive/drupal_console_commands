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
    $this->setName('site:compose')
      // @todo use: ->setDescription($this->trans('commands.site.compose.description'))
      ->setDescription('Runs composer installer')
      ->addArgument(
        'site-name',
        InputArgument::REQUIRED,
        // @todo use: $this->trans('commands.site.compose.site-name.description')
        'The site name that is mapped to a repo in sites.yml'
      )->addOption(
        'destination-directory',
        '-D',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.compose.site-name.options')
        'Specify the destination of the site compose if different than the global destination found in sites.yml'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    if (!file_exists($this->destination . 'composer.json')) {
      $message = sprintf('The file composer.json is missing on %s',
        $this->destination
      );
      throw new SiteCommandException($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
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
