<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\CheckoutCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Checkout;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidOptionException;
use DennisDigital\Drupal\Console\Command\Site\AbstractCommand;

/**
 * Class SiteCheckoutCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class CheckoutCommand extends AbstractCommand {
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

    $this->setName('site:checkout')
      ->setDescription('Checkout a repository');

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

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Get command based one branch/tag/revision option.
    foreach ($this->refTypes as $refType) {
      if ($input->hasOption($refType) && $input->getOption($refType) !== NULL) {
        $commandName = 'site:checkout:' . $refType;
        $command = $this->getApplication()->find($commandName);
        $input->setArgument('command', $commandName);
      }
    }

    if (!isset($command)) {
      $command = $this->getApplication()->find('site:checkout:branch');
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
