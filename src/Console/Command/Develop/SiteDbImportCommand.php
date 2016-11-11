<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteDbImportCommand.
 *
 * Imports local dumps or installs a fresh site if no dump is found.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VM\Console\Command\Exception\SiteCommandException;

/**
 * Class SiteDbImportCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteDbImportCommand extends SiteBaseCommand {

  /**
   * The Db dump file.
   *
   * @var
   */
  protected $filename = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    // Use same properties as SiteSettingsDbCommand.
    $siteSettingsDbCommand = new SiteSettingsDbCommand();
    $this->inheritArguments($siteSettingsDbCommand);
    $this->inheritOptions($siteSettingsDbCommand);

    $this->setName('site:db:import')
      // @todo use: ->setDescription($this->trans('commands.site.settings.db.description'))
      ->setDescription('Imports local dump or does a fresh install.');
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

    //@todo try to read db first

    // Append web/sites/default to destination.
    $this->destination .= 'web/sites/default/';

    $command = sprintf('cd %s; %s; %s; %s',
      $this->destination,
      'chmod 777 ../default',
      'chmod 777 settings.php',
      'drush si -y config_installer' //@todo this is hardcoded for now, but needs to go to sites.yml
    );
    $this->io->commentBlock($command);

    // Run.
    $shellProcess = $this->get('shell_process');

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success(sprintf('Site installed on %s', $this->destination));
    }
    else {
      throw new SiteCommandException($shellProcess->getOutput());
    }
  }
}
