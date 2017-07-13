<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\AbstractSiteCheckoutCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class AbstractSiteCheckoutCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
abstract class AbstractSiteCheckoutCommand extends SiteBaseCommand {

  /**
   * Stores repo information.
   *
   * @var array Repo.
   */
  protected $repo;

  /**
   * The branch/tag to checkout.
   *
   * @var string ref
   */
  protected $ref;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    // Custom options.
    $this->addOption(
      'ignore-changes',
      '',
      InputOption::VALUE_NONE,
      'Ignore local changes when checking out the site'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    // Validate repo.
    $this->validateRepo();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    // Validate repo.
    $this->validateRepo();
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
    $command = sprintf('git clone %s %s',
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

    // Checkout the tag.
    $this->gitCheckout();

    return TRUE;
  }

  /**
   * Helper to check out a tag.
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

}
