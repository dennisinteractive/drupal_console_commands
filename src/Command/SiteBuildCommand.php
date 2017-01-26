<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteBuildCommand.
 *
 * Builds the site by calling various commands.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteBuildCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteBuildCommand extends SiteBaseCommand {

  /**
   * Stores branch information.
   *
   * @var array Branch.
   */
  protected $branch;

  /**
   * @var ShellProcess
   */
  private $process;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:build')
      ->setDescription('Build a site');

    // Custom options.
    $this->addOption(
        'branch',
        '-B',
        InputOption::VALUE_OPTIONAL,
        'Specify which branch to build if different than the global branch found in sites.yml'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    $commands = [
      'drupal site:checkout %s',
//      'drupal site:compose %s',
//      'drupal site:npm %s',
//      'drupal site:grunt %s',
//      'drupal site:settings:db %s',
//      'drupal site:phpunit:setup %s',
//      'drupal site:behat:setup %s',
//      'drupal site:settings:memcache %s',
//      'drupal site:db:import %s',
    ];

    foreach ($commands as $item) {
      $command = sprintf(
        $item,
        $this->siteName
      );
      $this->process($command);
      //print (shell_exec($command));
    }

    $commands = [
      'cd %s/web; drush updb -y;',
      'cd %s/web; drush cr;',
    ];

    foreach ($commands as $item) {
      $command = sprintf(
        $item,
        $this->destination
      );
      $this->process($command);
    }
  }

  protected function process($command) {
    $this->process = new Process($command, null, null, 'subscriptions');
    //$this->process->setWorkingDirectory($this->destination);
    $this->process->enableOutput();
    $this->process->setTimeout(null);
    $this->process->setPty(true);
    $this->process->run(
      function ($type, $buffer) {
        $this->io->write($buffer);
      }
    );

    if (!$this->process->isSuccessful()) {
      throw new SiteCommandException($this->process->getOutput());
    }

//    $shellProcess = $this->getShellProcess();
//    $shellProcess->process->enableOutput();
//
//    if ($shellProcess->exec($command, TRUE)) {
//      $this->io->writeln($shellProcess->getOutput());
//    }
//    else {
//      throw new SiteCommandException($shellProcess->getOutput());
//    }
  }

}
