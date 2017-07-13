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
use DennisDigital\Drupal\Console\Command\Site\BaseCommand;

/**
 * Class TagCommand
 *
 * @package DennisDigital\Drupal\Console\Command\Site\Checkout
 */
class TagCommand extends BaseCommand {

  /**
   * Stores repo information.
   *
   * @var array Repo.
   */
  protected $repo;

  /**
   * Stores tag information.
   *
   * @var string tag.
   */
  protected $tag;

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
        'ignore-changes',
        '',
        InputOption::VALUE_NONE,
        'Ignore local changes when checking out the site'
      )->addOption(
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

    // Validate repo.
    $this->validateRepo();

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

    // Validate repo.
    $this->validateRepo();

    // Validate tag.
    $this->validateTag($input);

    if ($this->tag == $this->currentTag) {
      $this->io->commentBlock('Current tag selected, skipping checkout command.');
      return;
    }

    $this->io->comment(sprintf('Checking out %s (%s) on %s',
      $this->siteName,
      $this->tag,
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
      $this->tag = $input->getOption('tag');
    }
    else {
      throw new CommandException('Tag must be specified.');
    }
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
          'If you want to check out the site without committing the changes use --ignore-changes.',
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

    // Checkout the tag.
    $this->gitCheckout();

    return TRUE;
  }

  /**
   * Helper to check out a tag.
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
        'git checkout %s ',
      $this->shellPath($this->destination),
      $this->tag
    );

    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Checked out %s', $this->tag));
    }
    else {
      throw new CommandException($shellProcess->getOutput());

    }

    return TRUE;
  }

}
