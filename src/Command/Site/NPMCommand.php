<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\NPMCommand.
 *
 * Runs NPM.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class NPMCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class NPMCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:npm')
      ->setDescription('Runs NPM.');
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

    $this->io->comment(sprintf('Running NPM on %s',
      $this->getWebRoot()
    ));

    $commands = [];
    $commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));
    $commands[] = sprintf('find . -type d \( -name node_modules -o -name vendor -o -name contrib -o -path ./core \) -prune -o -name package.json -execdir sh -c "pwd; npm install" \;',
      $this->shellPath($this->getWebRoot())
    );
    $command = implode(' && ', $commands);

    // Run.
    $shellProcess = $this->getShellProcess();
    $this->io->commentBlock($command);

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success('NPM job completed');
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }
  }

}
