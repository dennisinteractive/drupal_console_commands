<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\CheckoutCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Checkout;

use Symfony\Component\Console\Input\InputOption;
use DennisDigital\Drupal\Console\Command\Site\AbstractCommand;

/**
 * Class SiteCheckoutCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
abstract class AbstractCheckoutCommand extends AbstractCommand {
  /**
   * Types of refs that can be checked out.
   *
   * @var array
   */
  protected $refTypes = ['tag', 'branch'];

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    // Custom options.
    $this->addOption(
      'force',
      '',
      InputOption::VALUE_NONE,
      'Will force the checkout and replace all local changes'
    );

    // Allow different ref types to be checked out.
    foreach ($this->refTypes as $refType) {
      $this->addOption(
        $refType,
        '-' . strtoupper(substr($refType, 0, 1)),
        InputOption::VALUE_OPTIONAL,
        sprintf('Specify which %s to checkout.', $refType)
      );
    }
  }
}
