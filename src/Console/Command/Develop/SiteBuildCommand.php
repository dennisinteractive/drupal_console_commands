<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteBuildCommand.
 */

namespace VM\Console\Command\Develop;

/**
 * Class SiteBuildCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteBuildCommand extends SiteBaseCommand {
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('site:build')
      // @todo use: ->setDescription($this->trans('commands.site.build.description'))
      ->setDescription('Check out a site and installs composer.json')
      ->addArgument(
        'site-name',
        InputArgument::REQUIRED,
        // @todo use: $this->trans('commands.site.compose.site-name.description')
        'The site name that is mapped to a repo in sites.yml'
      )->addOption(
        'destination-directory',
        '-D',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.compose.site-name.options')
        'Specify the destination of the site compose if different than the global destination found in sites.yml'
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
  }
}
