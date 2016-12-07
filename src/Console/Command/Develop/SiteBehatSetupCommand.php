<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteBehatSetupCommand.
 *
 * Create behat.yml from template.
 */

namespace VM\Console\Command\Develop;

/**
 * Class SiteBehatSetupCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteBehatSetupCommand extends SiteBaseConfigCommand {

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename = 'tests/behat.yml';

  /**
   * The template.
   *
   * @var
   */
  protected $template = 'tests/behat.yml.dist';

  /**
   * The console command.
   *
   * @var
   */
  protected $commandName = 'site:behat:setup';
}
