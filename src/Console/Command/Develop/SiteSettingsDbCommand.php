<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteSettingsDbCommand.
 *
 * Create Db configurations.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Site\InstallCommand;
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
    parent::configure();

    $this->setName('site:settings:db')
      // @todo use: ->setDescription($this->trans('commands.site.settings.db.description'))
      ->setDescription('Generates settings.db.php for a given site.');

    // Inherit arguments and options from InstallCommand().
    $command = new InstallCommand();
    $this->inheritArguments($command);
    $this->inheritOptions($command);
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

    // Append web/sites/default to destination.
    $this->destination .= 'web/sites/default/';

    // Validation.
    if (!file_exists($this->destination . 'settings.php')) {
      $message = sprintf('The file settings.php is missing on %s',
        $this->destination
      );
      throw new SiteCommandException($message);
    }

    if (is_null($input->getOption('db-name'))) {
      $input->setOption('db-name',  $this->siteName);
    }

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
  'port' => $db_port,
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
