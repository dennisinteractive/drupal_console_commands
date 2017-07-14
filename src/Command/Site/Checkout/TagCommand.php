<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Checkout.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Checkout;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Site\Exception\CommandException;

/**
 * Class TagCommand
 *
 * @package DennisDigital\Drupal\Console\Command\Site\Checkout
 */
class TagCommand extends AbstractCheckoutCommand {
  /**
   * Stores current tag of the checked out code.
   *
   * @var string current tag.
   */
  protected $currentTag;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:checkout:tag')
      ->setDescription('Checkout a repository by tag');

    // Custom options.
    $this->addOption(
      'tag',
      '-T',
      InputOption::VALUE_OPTIONAL,
      'Specify which tag to checkout if different than the global tag found in sites.yml'
    );
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
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    // Validate tag.
    $this->validateTag($input);

    if ($this->ref == $this->currentTag) {
      $this->io->commentBlock('Current tag selected, skipping checkout command.');
      return;
    }

    $this->io->comment(sprintf('Checking out %s (%s) on %s',
      $this->siteName,
      $this->ref,
      $this->destination
    ));

    switch ($this->repo['type']) {
      case 'git':
        // Check if repo exists and has any changes.
        if ($this->fileExists($this->destination) &&
          $this->fileExists($this->destination . '.' . $this->repo['type'])
        ) {
          if ($input->hasOption('ignore-changes') &&
            !$input->getOption('ignore-changes')
          ) {
            // Check for uncommitted changes.
            $this->gitDiff();
          }
          // Check out tag on existing repo.
          $this->gitCheckout();
        }
        else {
          // Clone repo.
          $this->gitClone();
        }
        break;

      default:
        $message = sprintf('%s is not supported.',
          $this->repo['type']
        );
        throw new CommandException($message);
    }
  }

  /**
   * Helper to validate tag option.
   *
   * @param InputInterface $input
   *
   * @throws CommandException
   */
  protected function validateTag(InputInterface $input) {
    if ($input->hasOption('tag') &&
      !is_null($input->getOption('tag'))
    ) {
      // Use config from parameter.
      $this->ref = $input->getOption('tag');
    }
    else {
      throw new CommandException('Tag must be specified.');
    }
  }

}
