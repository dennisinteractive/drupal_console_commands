<?php

/**
 * @file
 * Contains \VM\Console\Develop\SitePHPUnitSetupCommand.
 *
 * Create phpunit.xml from template.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SitePHPUnitSetupCommand
 *
 * @package VM\Console\Command\Develop
 */
class SitePHPUnitSetupCommand extends SiteBaseConfigCommand {

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename = 'phpunit.xml';

  /**
   * The template.
   *
   * @var
   */
  protected $template = 'phpunit.xml.dist';

  /**
   * The console command.
   *
   * @var
   */
  protected $commandName = 'site:phpunit:setup';

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (empty($this->config['db']['db-url'])) {
      // Create db-url if it hasn't been provided.
      // e.g. mysql://username:password@localhost/databasename
      $password = '';
      if (!empty($this->config['db']['db-password'])) {
        $password = ':' . $this->config['db']['db-password'];
      }
      $this->config['db']['db-url'] = sprintf('%s://%s%s@%s/%s',
        $this->config['db']['db-type'],
        $this->config['db']['db-user'],
        $password,
        $this->config['db']['db-host'],
        $this->config['db']['db-name']);
    }
    parent::execute($input, $output);
  }
}
