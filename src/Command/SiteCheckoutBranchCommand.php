<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteCheckoutBranchCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteCheckoutBranchCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteCheckoutBranchCommand extends SiteBaseCommand {

  /**
   * Stores repo information.
   *
   * @var array Repo.
   */
  protected $repo;

  /**
   * Stores branch information.
   *
   * @var array Branch.
   */
  protected $branch;

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
      ->setDescription('Checkout a repo');

    // Custom options.
    $this->addOption(
        'ignore-changes',
        '',
        InputOption::VALUE_NONE,
        'Ignore local changes when checking out the site'
      )->addOption(
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

    // Validate repo.
    $this->validateRepo();

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

    // Validate repo.
    $this->validateRepo();

    // Validate branch.
    $this->validateBranch($input);

    if ($this->branch == $this->currentBranch) {
      $this->io->commentBlock('Current branch selected, skipping checkout command.');
      return;
    }

    $this->io->comment(sprintf('Checking out %s (%s) on %s',
      $this->siteName,
      $this->branch,
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
        throw new SiteCommandException($message);
    }
  }

  /**
   * Helper to validate repo.
   *
   * @throws SiteCommandException
   *
   * @return string Repo url
   */
  protected function validateRepo() {
    if (isset($this->config['repo'])) {
      $this->repo = $this->config['repo'];
    }
    else {
      throw new SiteCommandException('Repo not found in sites.yml');
    }

    return $this->repo;
  }

  /**
   * Helper to validate branch option.
   *
   * @param InputInterface $input
   *
   * @throws SiteCommandException
   */
  protected function validateBranch(InputInterface $input) {
    if ($input->hasOption('branch') &&
      !is_null($input->getOption('branch'))
    ) {
      // Use config from parameter.
      $this->branch = $input->getOption('branch');
    }
    elseif (isset($this->config['repo']['branch'])) {
      // Use config from sites.yml.
      $this->branch = $this->config['repo']['branch'];
    }
    else {
      $this->branch = '8.x';
    }

    // Update input.
    if ($input->hasOption('branch')) {
      $input->setOption('branch', $this->branch);
    }
  }

  /**
   * Helper to detect local modifications.
   *
   * @return TRUE If everything is ok.
   *
   * @throws SiteCommandException
   */
  protected function gitDiff() {
    $command = sprintf(
      'cd %s && git diff-files --name-status -r --ignore-submodules',
      $this->shellPath($this->destination)
    );

    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      if (!empty($shellProcess->getOutput())) {
        $message = sprintf('You have uncommitted changes on %s' . PHP_EOL .
          'Please commit or revert your changes before checking out the site.' . PHP_EOL .
          'If you want to check out the site without committing the changes use --ignore-changes.',
          $this->destination
        );
        throw new SiteCommandException($message);
      }
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());
    }

    return TRUE;
  }

  /**
   * Helper to do the actual clone command.
   *
   * @return bool TRUE If successful.
   *
   * @throws SiteCommandException
   */
  protected function gitClone() {
    $command = sprintf('git clone --branch %s %s %s',
      $this->branch,
      $this->repo['url'],
      $this->shellPath($this->destination)
    );
    $this->io->commentBlock($command);

    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Repo cloned on %s', $this->destination));
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());
    }

    return TRUE;
  }

  /**
   * Helper to check out a branch.
   *
   * @return bool TRUE If successful.
   *
   * @throws SiteCommandException
   */
  protected function gitCheckout() {
    $command = sprintf(
        'cd %s && ' .
        'git fetch --all && ' .
        'chmod 777 web/sites/default && ' .
        'chmod 777 web/sites/default/settings.php && ' .
        'git checkout %s ',
      $this->shellPath($this->destination),
      $this->branch
    );

    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Checked out %s', $this->branch));
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());

    }

    return TRUE;
  }

  /**
   * Pulls a list of branches from remote.
   *
   * @return mixed
   * @throws SiteCommandException
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
      throw new SiteCommandException($shellProcess->getOutput());

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
