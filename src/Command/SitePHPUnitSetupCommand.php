<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SitePHPUnitSetupCommand.
 *
 * Create phpunit.xml from template.
 */

namespace DennisDigital\Drupal\Console\Command;

/**
 * Class SitePHPUnitSetupCommand
 *
 * @package DennisDigital\Drupal\Console\Command
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
  protected function generateConfigFile() {
    // Setup default scheme to http if not specified.
    if (empty($this->config['scheme'])) {
      $this->runtimeConfig['scheme'] = 'http';
    }

    // Create db-url if it hasn't been provided.
    // e.g. mysql://username:password@localhost/databasename
    if (empty($this->config['db']['url'])) {
      $password = '';
      if (!empty($this->config['db']['password'])) {
        $password = ':' . $this->config['db']['password'];
      }
      $this->runtimeConfig['db']['url'] = sprintf('%s://%s%s@%s/%s',
        $this->config['db']['type'],
        $this->config['db']['user'],
        $password,
        $this->config['db']['host'],
        $this->config['db']['name']);
    }

    parent::generateConfigFile();
  }
}
