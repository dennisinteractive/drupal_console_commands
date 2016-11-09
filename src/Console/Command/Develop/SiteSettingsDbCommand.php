<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteSettingsDbCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VM\Console\Command\Exception\SiteCommandException;

/**
 * Class SiteSettingsDbCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteSettingsDbCommand extends SiteBaseCommand {

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename = 'settings.db.php';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('site:settings:db')
      // @todo use: ->setDescription($this->trans('commands.site.settings.db.description'))
      ->setDescription('Generates settings.db.php for a given site.')
      ->addArgument(
        'site-name',
        InputArgument::REQUIRED,
        // @todo use: $this->trans('commands.site.settings.db.site-name.description')
        'The site name that is mapped to a repo in sites.yml'
      )->addOption(
        'destination-directory',
        '-D',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.db.site-name.options')
        'Specify the destination of the site settings.db.php if different than the global destination found in sites.yml'
      )->addOption(
        'db-type',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.db.type')
        'Database type',
        'mysql'
      )->addOption(
        'db-host',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.db.host')
        'Database host',
        '127.0.0.1'
      )->addOption(
        'db-port',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.db.port')
        'Database port',
        '3306'
      )->addOption(
        'db-name',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.db.name')
        'Database name. [default: site machine name from sites.yml]'
      )->addOption(
        'table-prefix',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.db.prefix')
        'Table prefix.',
        ''
      )->addOption(
        'db-user',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.db.user')
        'Database user',
        'root'
      )->addOption(
        'db-pass',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.db.pass')
        'Database password',
        ''
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    if (!file_exists($this->destination . 'settings.php')) {
      $message = sprintf('The file settings.php is missing on %s',
        $this->destination
      );
      throw new SiteCommandException($message);
    }

    if (is_null($input->getOption('db-name'))) {
      $input->setOption('db-name',  $this->siteName);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    // Remove existing file.
    $file = $this->destination . $this->filename;
    if (file_exists($file)) {
      unlink($file);
    }

    // Prepare content.
    $db_name = $input->getOption('db-name');
    $db_user = $input->getOption('db-user');
    $db_pass = $input->getOption('db-pass');
    $table_prefix = $input->getOption('table-prefix');
    $db_host = $input->getOption('db-host');
    $db_port = $input->getOption('db-port');
    $db_type = $input->getOption('db-type');
    $namespace = 'Drupal\\Core\\Database\\Driver\\' . $db_type;

    $content = <<<EOF
<?php
/**
 * DB Settings.
 */
\$databases['default']['default'] = array(
  'database' => '$db_name',
  'username' => '$db_user',
  'password' => '$db_pass',
  'prefix' => '$table_prefix',
  'host' => '$db_host',
  'port' => '$db_port',
  'driver' => '$db_type',
  'namespace' => '$namespace',
);
EOF;

    file_put_contents($file, $content);

    // Check file.
    if (file_exists($file)) {
      $this->io->success(sprintf('Generated %s',
          $file)
      );
    }
    else {
      throw new SiteCommandException('Error generating %s',
        $file
      );
    }
  }
}
