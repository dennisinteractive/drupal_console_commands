<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Checkout.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Checkout;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class TagCommand
 *
 * @package DennisDigital\Drupal\Console\Command\Site\Checkout
 */
class TagCommand extends AbstractRefCommand {
  /**
   * Types of refs that can be checked out.
   *
   * @var array
   */
  protected $refTypes = ['tag'];

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:checkout:tag')
      ->setDescription('Checkout a repository by tag');
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    $tag = $input->getOption('tag');
    if (!$tag) {
      $tag = $this->io->ask(
        $this->trans('Enter a tag')
      );

      $input->setOption('tag', $tag);
    }
  }

  /**
   * Validate tag option.
   *
   * @inheritdoc
   */
  protected function getRef(InputInterface $input) {
    if ($input->hasOption('tag') &&
      !is_null($input->getOption('tag'))
    ) {
      // Use config from parameter.
      return $input->getOption('tag');
    }
    throw new CommandException('Tag must be specified.');
  }

}
