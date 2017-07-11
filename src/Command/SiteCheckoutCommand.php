<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteCheckoutCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteCheckoutCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteCheckoutCommand extends SiteBaseCommand {

  /**
   * Stores repo information.
   *
   * @var array Repo.
   */
  protected $repo;

  /**
   * Stores tag/branch ref.
   *
   * @var string ref.
   */
  protected $ref;

  /**
   * Stores current tag/branch of the checked out code.
   *
   * @var string currentRef.
   */
  protected $currentRef;

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
      )->addOption(
        'branch',
        '-B',
        InputOption::VALUE_OPTIONAL,
        'Specify which branch to checkout if different than the global branch found in sites.yml'
      )->addOption(
        'tag',
        '-T',
        InputOption::VALUE_OPTIONAL,
        'Specify which tag to checkout'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    // Validate repo.
    $this->validateRepo();

    if (!$this->getRefOption($input)) {
      $remoteBranches = $this->getRemoteBranches();
      $defaultBranch = $this->getDefaultBranch();
      $this->currentRef = $this->getCurrentRef();

      $options = array_values(array_unique(array_merge(
        ['8.x'],
        [$defaultBranch],
        [$this->currentRef],
        $remoteBranches
      )));

      $branch = $this->io->choice(
        $this->trans('Select a branch'),
        $options,
        isset($this->currentRef) ? $this->currentRef : $defaultBranch,
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

    // Validate ref.
    $this->validateRef($input);

    if ($this->ref == $this->currentRef) {
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
   * Helper to validate tag and branch options.
   *
   * @param InputInterface $input
   *
   * @throws SiteCommandException
   */
  protected function validateRef(InputInterface $input) {
    if ($ref = $this->getRefOption($input)) {
      // Use config from parameter.
      $this->ref = $ref;
    }
    elseif (isset($this->config['repo']['branch'])) {
      // Use config from sites.yml.
      $this->ref = $this->config['repo']['branch'];
    }
    else {
      $this->ref = '8.x';
    }
  }

  /**
   * Helper to get user input tag/branch.
   *
   * @param InputInterface $input
   * @return mixed
   */
  protected function getRefOption(InputInterface $input) {
    foreach (['tag', 'branch'] as $type) {
      if ($input->hasOption($type) && !is_null($input->getOption($type))) {
        return $input->getOption($type);
      }
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
      $this->shellPath($this->directory)
    );

    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      if (!empty($shellProcess->getOutput())) {
        $message = sprintf('You have uncommitted changes on %s' . PHP_EOL .
          'Please commit or revert your changes before checking out the site.' . PHP_EOL .
          'If you want to check out the site without committing the changes use --ignore-changes.',
          $this->directory
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
      $this->ref,
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
      $this->ref
    );

    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Checked out %s', $this->ref));
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
   * Helper to retrieve the current working tag/branch on the site's directory.
   *
   * @return mixed
   */
  protected function getCurrentRef() {
    if ($this->fileExists($this->destination)) {
      // Get branch from site directory.
      $command = sprintf('cd %s && git describe --all',
        $this->shellPath($this->destination)
      );

      $shellProcess = $this->getShellProcess();

      if ($shellProcess->exec($command, TRUE)) {
        return $shellProcess->getOutput();
      }
    }
  }

}
