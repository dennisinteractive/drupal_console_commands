<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Checkout\BranchCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Checkout;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class BranchCommand
 *
 * @package DennisDigital\Drupal\Console\Command\Site\Checkout
 */
class BranchCommand extends AbstractCheckoutCommand {
  /**
   * Stores current branch of the checked out code.
   *
   * @var array currentBranch.
   */
  protected $currentBranch;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:checkout:branch')
      ->setDescription('Checkout a repository by branch');

    // Custom options.
    $this->addOption(
      'branch',
      '-B',
      InputOption::VALUE_OPTIONAL,
      'Specify which branch to checkout if different than the global branch found in sites.yml'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    $remoteBranches = $this->getRemoteBranches();
    $defaultBranch = $this->getDefaultBranch();
    $this->currentBranch = $this->getCurrentBranch();

    $branch = $input->getOption('branch');
    if (!$branch) {

      $options = array_values(array_unique(array_merge(
        ['8.x'],
        [$defaultBranch],
        [$this->currentBranch],
        $remoteBranches
      )));

      $branch = $this->io->choice(
        $this->trans('Select a branch'),
        $options,
        isset($this->currentBranch) ? $this->currentBranch : $defaultBranch,
        TRUE
      );

      $input->setOption('branch', reset($branch));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    // Validate branch.
    $this->validateBranch($input);

    if ($this->ref == $this->currentBranch) {
      $this->io->commentBlock('Current branch selected, skipping checkout command.');
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
          // Check out branch on existing repo.
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
   * Helper to validate branch option.
   *
   * @param InputInterface $input
   *
   * @throws CommandException
   */
  protected function validateBranch(InputInterface $input) {
    if ($input->hasOption('branch') &&
      !is_null($input->getOption('branch'))
    ) {
      // Use config from parameter.
      $this->ref = $input->getOption('branch');
    }
    elseif (isset($this->config['repo']['branch'])) {
      // Use config from sites.yml.
      $this->ref = $this->config['repo']['branch'];
    }
    else {
      $this->ref = '8.x';
    }

    // Update input.
    if ($input->hasOption('branch')) {
      $input->setOption('branch', $this->ref);
    }
  }

  /**
   * Pulls a list of branches from remote.
   *
   * @return mixed
   * @throws CommandException
   */
  protected function getRemoteBranches() {
    $command = sprintf('git ls-remote --heads %s',
      $this->repo['url']
    );

    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      preg_match_all("|refs/heads/(.*)|", $shellProcess->getOutput(), $matches);
      if (!empty($matches[1] && is_array($matches[1]))) {
        return $matches[1];
      }
    }
    else {
      throw new CommandException($shellProcess->getOutput());

    }
  }

  /**
   * Helper to retrieve the default branch from yml.
   *
   * @return mixed
   */
  protected function getDefaultBranch() {
    // Get branch from yml.
    if (isset($this->config['repo']['branch'])) {
      // Populate branches from config.
      return $this->config['repo']['branch'];
    }
  }

  /**
   * Helper to retrieve the current working branch on the site's directory.
   *
   * @return mixed
   */
  protected function getCurrentBranch() {
    if ($this->fileExists($this->destination)) {
      // Get branch from site directory.
      $command = sprintf('cd %s && git branch',
        $this->shellPath($this->destination)
      );

      $shellProcess = $this->getShellProcess();

      if ($shellProcess->exec($command, TRUE)) {
        preg_match_all("|\*\s(.*)|", $shellProcess->getOutput(), $matches);
        if (!empty($matches[1] && is_array($matches[1]))) {
          return reset($matches[1]);
        }
      }
    }
  }

}
