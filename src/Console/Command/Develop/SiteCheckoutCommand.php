<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteCheckoutCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VM\Console\Command\Exception\SiteCommandException;

/**
 * Class SiteCheckoutCommand
 *
 * @package VM\Console\Command\Develop
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
      // @todo use: ->setDescription($this->trans('commands.site.checkout.description'))
      ->setDescription('Checkout a repo');

    // Custom options.
    $this->addOption(
        'ignore-changes',
        '',
        InputOption::VALUE_NONE,
        // @todo use: $this->trans('commands.site.checkout.ignore-changes')
        'Ignore local changes when checking out the site'
      )->addOption(
        'branch',
        '-B',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.checkout.branch')
        'Specify which branch to checkout if different than the global branch found in sites.yml'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    $siteConfig = $this->config['sites'][$this->siteName];
    $this->repo = $siteConfig['repo'];

    // Loads default branch settings.
    if (!is_null($input->getOption('branch'))) {
      $this->branch = $input->getOption('branch');
    }
    else {
      if (isset($this->repo['branch'])) {
        $this->branch = $this->repo['branch'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->io->writeln(sprintf('Checking out %s (%s)',
      $this->siteName,
      $this->branch
    ));

    switch ($this->repo['type']) {
      case 'git':
        // Check if repo exists and has any changes.
        if (file_exists($this->destination) &&
          file_exists($this->destination . '.' . $this->repo['type'])
        ) {
          if (!$input->getOption('ignore-changes')) {
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

    $shellProcess = $this->get('shell_process');

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

    $shellProcess = $this->get('shell_process');

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
    $command = sprintf('cd %s; git checkout -B %s',
      $destination,
      $branch
    );

    $shellProcess = $this->get('shell_process');

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->success(sprintf('Checked out %s', $branch));
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());

    }

    return TRUE;
  }
}
