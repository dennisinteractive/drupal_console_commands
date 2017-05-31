<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteSetupCommand.
 *
 * Sets up the necessary site variables.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteSetupCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteSetupCommand extends SiteBaseCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:base:setup')
      // @todo use: ->setDescription($this->trans('commands.site.compose.description'))
      ->setDescription('Sets up the necessary site variables.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    $this->io->success('Stuff works.');
  }
}
