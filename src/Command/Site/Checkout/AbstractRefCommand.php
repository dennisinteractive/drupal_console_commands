<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Checkout\AbstractRefCommand.
 *
 * Does repo checkouts.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Checkout;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class AbstractRefCommand
 *
 * @package DennisDigital\Drupal\Console\Command\Site\Checkout
 */
abstract class AbstractRefCommand extends AbstractCheckoutCommand {

  /**
   * Stores repo information.
   *
   * @var array Repo.
   */
  protected $repo;

  /**
   * The branch/tag to checkout.
   *
   * @var string
   */
  protected $ref;

  /**
   * Current branch/tag
   *
   * @var string
   */
  protected $currentRef;

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    // Validate repo.
    $this->validateRepo();

    $this->currentRef = $this->getCurrentRef();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    // Validate repo.
    $this->validateRepo();

    // Validate ref.
    $this->ref = $this->getRef($input);

    if ($this->ref == $this->currentRef) {
      $this->io->commentBlock('Current branch/tag selected, skipping checkout command.');
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
          if ($input->hasOption('force') &&
            !$input->getOption('force')
          ) {
            // Check for uncommitted changes.
            $this->gitDiff();
          }
          // Check out ref on existing repo.
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
   * Helper to validate repo.
   *
   * @throws CommandException
   *
   * @return string Repo url
   */
  protected function validateRepo() {
    if (isset($this->config['repo'])) {
      $this->repo = $this->config['repo'];
    }
    else {
      throw new CommandException('Repo not found in sites.yml');
    }

    return $this->repo;
  }

  /**
   * Helper to detect local modifications.
   *
   * @return TRUE If everything is ok.
   *
   * @throws CommandException
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
          'If you want to wipe your local changes use --force.',
          $this->destination
        );
        throw new CommandException($message);
      }
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }

    return TRUE;
  }

  /**
   * Helper to do the actual clone command.
   *
   * @return bool TRUE If successful.
   *
   * @throws CommandException
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
      throw new CommandException($shellProcess->getOutput());
    }

    // Checkout the tag/branch.
    $this->gitCheckout();

    return TRUE;
  }

  /**
   * Helper to check out a tag/branch.
   *
   * @return bool TRUE If successful.
   *
   * @throws CommandException
   */
  protected function gitCheckout() {
    $command = sprintf(
      'cd %s && ' .
      'git fetch --all && ' .
      'chmod 777 web/sites/default && ' .
      'chmod 777 web/sites/default/settings.php && ' .
      'git checkout %s --force',
      $this->shellPath($this->destination),
      $this->ref
    );

    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Checked out %s', $this->ref));
    }
    else {
      throw new CommandException($shellProcess->getOutput());

    }

    return TRUE;
  }

  /**
   * Helper to retrieve the current working branch on the site's directory.
   *
   * @return mixed
   */
  protected function getCurrentRef() {
    if ($this->fileExists($this->destination)) {
      // Get branch from site directory.
      $command = sprintf('cd %s && git branch',
        $this->shellPath($this->destination)
      );

      $shellProcess = $this->getShellProcess()->printOutput(FALSE);

      if ($shellProcess->exec($command, TRUE)) {
        preg_match_all("|\*\s(.*)|", $shellProcess->getOutput(), $matches);
        if (!empty($matches[1] && is_array($matches[1]))) {
          $match = explode(' ', trim(reset($matches[1]), '()'));
          return array_pop($match);
        }
      }
    }
  }

  /**
   * Get the requested ref (tag/branch).
   *
   * @param InputInterface $input
   * @return string
   */
  abstract protected function getRef(InputInterface $input);
}
