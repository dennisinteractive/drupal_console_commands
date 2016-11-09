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
        'Specify the destination of the site settings.db if different than the global destination found in sites.yml'
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

    if (!file_exists($this->destination . $this->filename)) {
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

    // Prepare array.
    $databases['default']['default'] = array(
      'database' => $input->getOption('db-name'),
      'username' => $input->getOption('db-user'),
      'password' => $input->getOption('db-pass'),
      'prefix' => $input->getOption('table-prefix'),
      'host' => $input->getOption('db-host'),
      'port' => $input->getOption('db-port'),
    );
    switch ($input->getOption('db-type')) {
      case 'mysql':
        $databases['default']['default']['namespace'] = 'Drupal\\Core\\Database\\Driver\\mysql';
        $databases['default']['default']['driver'] = 'mysql';
    }

    // Remove existing file.
    $file = $this->destination . '/settings.db.php';
    if (file_exists($file)) {
      unlink($file);
    }

    // Generate file.
    $content = '<?php' . PHP_EOL;
    $content .= '$databases[\'default\'][\'default\'] = ' . PHP_EOL;
    $content .= var_export($databases, TRUE);
    $content .= ';';
    file_put_contents($file, $content);

    // Check file.
    if (file_exists($this->destination . $this->filename)) {
      $this->io->success(sprintf('Generated %s',
        $this->destination . $this->filename)
      );
    }
    else {
      throw new SiteCommandException('Error generating %s',
        $this->destination . $this->filename
      );
    }
  }
}
