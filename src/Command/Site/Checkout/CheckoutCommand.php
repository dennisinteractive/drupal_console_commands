<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Checkout\CheckoutCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Checkout;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckoutCommand
 *
 * @package DennisDigital\Drupal\Console\Command\Site\Checkout
 */
class CheckoutCommand extends AbstractCheckoutCommand {
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:checkout')
      ->setDescription('Checkout a repository');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Get command based one branch/tag/revision option.
    $command = $this->getApplication()->find('site:checkout:branch');
    foreach ($this->refTypes as $refType) {
      if ($input->hasOption($refType) && $input->getOption($refType) !== NULL) {
        $commandName = 'site:checkout:' . $refType;
        $command = $this->getApplication()->find($commandName);
        $input->setArgument('command', $commandName);
      }
    }

    // Pass input parameters to specific checkout command.
    $parameters = $input->getArguments();
    foreach ($input->getOptions() as $name => $value) {
      $parameters['--' . $name] = $value;
    }

    $commandInput = new ArrayInput(array_filter($parameters));

    // Run checkout command.
    $command->run($commandInput, $output);
  }

}
