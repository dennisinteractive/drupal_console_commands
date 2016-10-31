<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteBuildCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class SiteBuildCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteBuildCommand extends Command {
  use CommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('site:build')
      // @todo use: ->setDescription($this->trans('commands.site.build.description'))
      ->setDescription(t('Check out a site and installs composer.json.'))
      ->addArgument(
        'site-name',
        InputArgument::REQUIRED,
        // @todo use: $this->trans('commands.site.build.site-name.description')
        t('The site name that is mapped to a repo in config.yml.')
      )->addOption(
        'destination-directory',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.build.site-name.options')
        t('Specify the destination of the site build if different than the global destination found in config.yml')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    /* Register your command as a service
     *
     * Make sure you register your command class at
     * config/services/namespace.yml file and add the `console.command` tag.
     *
     * develop_example:
     *   class: VM\Console\Command\Develop\SiteBuildCommand
     *   tags:
     *     - { name: console.command }
     *
     * NOTE: Make the proper changes on the namespace and class
     *       according your new command.
     *
     * DrupalConsole extends the SymfonyStyle class to provide
     * an standardized Output Formatting Style.
     *
     * Drupal Console provides the DrupalStyle helper class:
     */
    $io = new DrupalStyle($input, $output);
    $io->simple(t('Building :site', array(
        ':site' => $input->getArgument('site-name')
      ))
    );

    // Reading user input argument.
    // $input->getArgument('site-name');

    // Reading user input option.
    // $input->getOption('OPTION_NAME');
  }
}
