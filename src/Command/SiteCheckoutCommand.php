<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteCheckoutCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SiteCheckoutCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteCheckoutCommand extends SiteBaseCommand {
  protected $refTypes = ['tag', 'branch'];

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:checkout')
      ->setDescription('Checkout a repo');

    // Custom options.
    $this->addOption(
      'ignore-changes',
      '',
      InputOption::VALUE_NONE,
      'Ignore local changes when checking out the site'
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
      throw new InvalidOptionException('Please provide one of the following options: ' . implode(', ', $this->refTypes));
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
