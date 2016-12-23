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
   * Stores branch information.
   *
   * @var array Branch.
   */
  protected $branch;

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
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    $branch = $input->getOption('branch');
    if (!$branch) {
        // Typical branches.
        $branches = ['8.x', 'master'];

        if (isset($this->config['repo']['branch'])) {
          // Populate branches from config.
          $siteBranch = $this->config['repo']['branch'];
        }

        $branch = $this->io->choice(
            $this->trans('Select a branch'),
            array_values(array_unique(array_merge([$siteBranch], $branches))),
            isset($siteBranch) ? $siteBranch : '8.x',
            true
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
    $this->_validateRepo();

    // Validate branch.
    $this->_validateBranch($input);

    $this->io->comment(sprintf('Checking out %s (%s) on %s',
      $this->siteName,
      $this->branch,
      $this->destination
    ));

    switch ($this->repo['type']) {
      case 'git':
        // Check if repo exists and has any changes.
        if (file_exists($this->destination) &&
          file_exists($this->destination . '.' . $this->repo['type'])
        ) {
          if ($input->hasOption('ignore-changes') &&
            !$input->getOption('ignore-changes')
          ) {
            // Check for uncommitted changes.
            $this->gitDiff($this->destination);
          }
          // Check out branch on existing repo.
          $this->gitCheckout($this->branch, $this->destination);
        }
        else {
          // Clone repo.
          $this->gitClone($this->branch,
            $this->repo['url'],
            $this->destination
          );
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
  protected function _validateRepo() {
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
  protected function _validateBranch(InputInterface $input) {
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
   * @param $directory The directory containing the git folder.
   *
   * @return TRUE If everything is ok.
   *
   * @throws SiteCommandException
   */
  protected function gitDiff($directory) {
    $command = sprintf(
      'cd %s; git diff-files --name-status -r --ignore-submodules',
      $directory
    );

    $shellProcess = $this->shellProcess;

    if ($shellProcess->exec($command, TRUE)) {
      if (!empty($shellProcess->getOutput())) {
        $message = sprintf('You have uncommitted changes on %s' . PHP_EOL .
          'Please commit or revert your changes before checking out the site.' . PHP_EOL .
          'If you want to check out the site without committing the changes use --ignore-changes.',
          $directory
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
   * @param $branch Branch name.
   * @param $repo Repo Url.
   * @param $destination Destination folder.
   *
   * @return bool TRUE If successful.
   *
   * @throws SiteCommandException
   */
  protected function gitClone($branch, $repo, $destination) {
    $command = sprintf('git clone --branch %s %s %s',
      $branch,
      $repo,
      $destination
    );
    $this->io->commentBlock($command);

    $shellProcess = $this->shellProcess;

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Repo cloned on %s', $destination));
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());
    }

    return TRUE;
  }

  /**
   * Helper to check out a branch.
   *
   * @param $branch Branch name.
   * @param $destination Destination folder.
   *
   * @return bool TRUE If successful.
   *
   * @throws SiteCommandException
   */
  protected function gitCheckout($branch, $destination) {
    $command = sprintf('cd %s; git fetch --all; git checkout %s',
      $destination,
      $branch
    );

    $shellProcess = $this->shellProcess;

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Checked out %s', $branch));
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());

    }

    return TRUE;
  }
}
