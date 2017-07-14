<?php

/**
 * @file
 * Contains DennisDigital\DennisDigital\Drupal\Console\Command\Shared\InstallArgumentsTrait.
 */

namespace DennisDigital\Drupal\Console\Command\Shared;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class InstallArgumentsTrait
 * @package DennisDigital\Drupal\Console\Command
 */
trait InstallArgumentsTrait
{

  /**
   * Allow to re-use options and arguments.
   * The options/arguments below were copied from \Drupal\AppConsole\Command\Site\InstallCommand.
   */
  protected function getSiteInstallArguments()
  {
    $this
      ->addArgument(
        'profile',
        InputArgument::OPTIONAL,
        $this->trans('commands.site.install.arguments.profile')
      )
      ->addOption(
        'langcode',
        '',
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.site.install.options.langcode')
      )
      ->addOption(
        'db-type',
        '',
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.site.install.options.db-type')
      )
      ->addOption(
        'db-file',
        '',
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.site.install.options.db-file')
      )
      ->addOption(
        'db-host',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.migrate.execute.options.db-host')
      )
      ->addOption(
        'db-name',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.migrate.execute.options.db-name')
      )
      ->addOption(
        'db-user',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.migrate.execute.options.db-user')
      )
      ->addOption(
        'db-pass',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.migrate.execute.options.db-pass')
      )
      ->addOption(
        'db-prefix',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.migrate.execute.options.db-prefix')
      )
      ->addOption(
        'db-port',
        '',
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.migrate.execute.options.db-port')
      )
      ->addOption(
        'site-name',
        '',
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.site.install.options.site-name')
      )
      ->addOption(
        'site-mail',
        '',
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.site.install.options.site-mail')
      )
      ->addOption(
        'account-name',
        '',
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.site.install.options.account-name')
      )
      ->addOption(
        'account-mail',
        '',
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.site.install.options.account-mail')
      )
      ->addOption(
        'account-pass',
        '',
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.site.install.options.account-pass')
      )
      ->addOption(
        'force',
        '',
        InputOption::VALUE_NONE,
        $this->trans('commands.site.install.options.force')
      );
  }
}
