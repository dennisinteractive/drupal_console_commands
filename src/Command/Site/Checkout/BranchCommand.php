<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Checkout\BranchCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Checkout;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class BranchCommand
 *
 * @package DennisDigital\Drupal\Console\Command\Site\Checkout
 */
class BranchCommand extends AbstractRefCommand {
  /**
   * Types of refs that can be checked out.
   *
   * @var array
   */
  protected $refTypes = ['branch'];

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:checkout:branch')
      ->setDescription('Checkout a repository by branch');
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    $remoteBranches = $this->getRemoteBranches();
    $defaultBranch = $this->getDefaultBranch();

    $branch = $input->getOption('branch');
    if (!$branch) {

      $options = array_filter(array_values(array_unique(array_merge(
        ['8.x'],
        [$defaultBranch],
        [$this->currentRef],
        $remoteBranches
      ))));

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
   * Get branch option.
   *
   * @inheritdoc
   */
  protected function getRef(InputInterface $input) {
    if ($input->hasOption('branch') &&
      !is_null($input->getOption('branch'))
    ) {
      // Use config from parameter.
      $ref = $input->getOption('branch');
    }
    elseif (isset($this->config['repo']['branch'])) {
      // Use config from sites.yml.
      $ref = $this->config['repo']['branch'];
    }
    else {
      $ref = '8.x';
    }

    // Update input.
    if ($input->hasOption('branch')) {
      $input->setOption('branch', $ref);
    }

    return $ref;
  }

  /**
   * Pulls a list of branches from remote.
   *
   * @return mixed
   * @throws CommandException
   */
  protected function getRemoteBranches() {
    $this->io->comment(sprintf('Fetching remote branches'));

    $command = sprintf('git ls-remote --heads %s',
      $this->repo['url']
    );

    $shellProcess = $this->getShellProcess()->printOutput(FALSE);
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

}
