<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\DbImportCommand.
 *
 * Imports local dumps or installs a fresh site if no dump is found.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Aws\S3\S3Client;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;
use DennisDigital\Drupal\Console\Command\Site\Shared\InstallArgumentsTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
/**
 * Class DbImportCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class DbImportCommand extends AbstractCommand {
  use InstallArgumentsTrait;

  /**
   * The Db dump file.
   *
   * @var
   */
  protected $filename = NULL;

  /**
   * The temporary path to this db.
   */
  protected $tmpFolder = '/tmp/';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:db:import')
      // @todo use: ->setDescription($this->trans('commands.site.settings.db.description'))
      ->setDescription('Imports local dump or does a fresh install.');

    // Re-use SiteInstall options and arguments.
    $this->getSiteInstallArguments();

    $this->addOption(
      'file',
      NULL,
      InputOption::VALUE_REQUIRED,
      $this->trans('commands.database.restore.options.file')
    );

    // Register S3 wrapper for use by php file functions.
    //
    // @todo ensure all configuration options for S3 including
    // access and secret keys to be passed in to ensure S3
    // wrapper can access the required buckets.
    $options = [
      'region' => 'eu-west-1',
      'version' => 'latest',
    ];
    $client = new S3Client($options);
    $client->registerStreamWrapper();
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

  }

  /**
   * Helper to check if the file passed as argument is using relative path.
   *
   * @param $path
   *
   * @return bool TRUE if is relative
   */
  protected function is_absolute_path($path) {
    return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path) > 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    // Check if a dump file has been specified.
    if ($input->hasOption('file') && !is_null($input->getOption('file'))) {
      $this->filename = $input->getOption('file');
    }
    // Check if a dump file is set in the yaml config
    elseif (isset($this->config['db']['dump'])) {
      // Use config from sites.yml.
      $this->filename = $this->config['db']['dump'];
    }
    else {
      throw new CommandException('Please specify a file to import the dump from');
    }

    // Override default values for these options (if empty).
    $override = array(
      'account-name' => $this->config['account-name'],
      'account-pass' => $this->config['account-pass'],
      'account-mail' => $this->config['account-mail'],
      'db-type' => $this->config['db']['type'],
      'db-host' => $this->config['db']['host'],
      'db-name' => $this->config['db']['name'],
      'db-user' => $this->config['db']['user'],
      'db-port' => $this->config['db']['port'],
      'db-pass' => isset($this->config['db']['pass']) ? $this->config['db']['pass'] : '',
    );
    foreach ($this->getDefinition()->getOptions() as $option) {
      $name = $option->getName();
      if (array_key_exists($name, $override) && is_null($input->getOption($name))) {
        $input->setOption($name, $override[$name]);
      }
    }

    // Populate installation options.
    $options = '';
    foreach ($this->getDefinition()->getOptions() as $option) {
      $name = $option->getName();

      // Ignore Drupal console variables.
      if ($name == 'env') {
        continue;
      }

      $value = $input->getOption($name);
      if (!empty($value)) {
        $options .= sprintf('--%s=%s ',
          $name,
          $value
        );
      }
    }

    // Temporary solution to recreate databases until Drupal console provides commands for this.
    $db = array(
      'name' => $override['db-name'],
      'type' => $override['db-type'],
      'host' => $override['db-host'],
      'user' => $override['db-user'],
      'pass' => $override['db-pass'],
      'port' => $override['db-port'],
    );
    $this->sqlDrop($db);
    $this->sqlCreate($db);

    // If we're installing from a dump that's not already in
    // our local destination, copy it to our local destination.
    if (!empty($this->filename)) {
      // Get dump.
      $this->filename = $this->getDump($this->filename);
    }

    // This code is repetitive so we can run config:import separately if we're
    // doing a fresh site install.
    if ($this->fileExists($this->filename)) {
      // Import dump.
      $commands = $this->getSqlImportCommands($options);

      $command = implode(' && ', $commands);

      $this->io->commentBlock($command);

      // Run.
      $shellProcess = $this->getShellProcess();

      if ($shellProcess->exec($command, TRUE)) {
        //$this->io->writeln($shellProcess->getOutput());
      }
      else {
        throw new CommandException($shellProcess->getOutput());
      }
    }
    else {
      // If a dump file wasn't found, do a fresh site install
      $commands = $this->getSiteInstallCommands($options);

      $command = implode(' && ', $commands);

      $this->io->commentBlock($command);

      // Run.
      $shellProcess = $this->getShellProcess();

      if ($shellProcess->exec($command, TRUE)) {
        //$this->io->writeln($shellProcess->getOutput());
      }
      else {
        throw new CommandException($shellProcess->getOutput());
      }

      // We run config:import separately so that if there's no config and it
      // fails we can continue. Previously, we checked the config folder, but
      // this was a quick fix.
      if ($this->getDrupalVersion() === 8) {
        $config_commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));
        $config_commands[] = 'drupal config:import';
        $config_command = implode(' && ', $config_commands);

        $this->io->commentBlock($config_command);

        try {
          $shellProcess->exec($config_command, TRUE);
        }
        catch (ProcessFailedException $e) {
        }
      }
    }

  }

  /**
   * Temporary function to drop databases.
   */
  protected function sqlDrop($db) {
    $command = sprintf('mysql --user=%s --password=%s --host=%s --port=%s -e"DROP DATABASE IF EXISTS %s"',
        $db['user'],
        $db['pass'],
        $db['host'],
        $db['port'],
        $db['name']
    );

    $this->io->commentBlock($command);

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      //$this->io->writeln($shellProcess->getOutput());
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }
  }

  /**
   * Temporary function to create databases.
   */
  protected function sqlCreate($db) {
    $command = sprintf('mysql --user=%s --password=%s --host=%s --port=%s -e"CREATE DATABASE IF NOT EXISTS %s"',
        $db['user'],
        $db['pass'],
        $db['host'],
        $db['port'],
        $db['name']
    );

    $this->io->commentBlock($command);

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      //$this->io->writeln($shellProcess->getOutput());
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }
  }

  /**
   * Helper to return list of commands to import sql dump.
   */
  protected function getSqlImportCommands() {
    $this->io->comment('Importing dump');

    // Drupal 7 only;
    if ($this->getDrupalVersion() === 7) {
      $commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));
      $commands[] = sprintf('drush sql-create -y');
      $commands[] = sprintf('drush sql-cli < %s', $this->filename);
      $commands[] = 'drush rr --no-cache-clear';
      $commands[] = 'drush rr --fire-bazooka';
      $commands[] = sprintf('drush user-password %s --password="%s"',
        $this->config['account-name'],
        $this->config['account-pass']
      );
    }

    // Drupal 8 only;
    if ($this->getDrupalVersion() === 8) {
      $commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));
      $commands[] = sprintf('drupal database:restore --file=%s', $this->filename);
      $commands[] = 'drupal cache:rebuild all';
      $commands[] = sprintf('drupal user:password:reset 1 %s', $this->config['account-pass']);
    }

    return $commands;
  }

  /**
   * Helper to return list of commands to install a site.
   */
  protected function getSiteInstallCommands($options) {
    $this->io->comment('Installing site');

    // Drupal 7 only;
    if ($this->getDrupalVersion() === 7) {
      $commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));
      $commands[] = sprintf('drush sql-create -y');
      $commands[] = sprintf('drush si -y %s %s', $this->profile, $options);
    }

    // Drupal 8 only;
    if ($this->getDrupalVersion() === 8) {
      $commands[] = sprintf('cd %s', $this->shellPath($this->getWebRoot()));
      // Install drupal from existing configuration, see https://weknowinc.com/blog/how-install-drupal-8-existing-configuration
      $commands[] = sprintf('drupal site:install %s %s --force', $this->profile, $options);
//      if ($this->fileExists($this->getWebRoot() . $this->getConfigUrl() . '/system.site.yml')) {
//        $commands[] = 'drupal config:import';
//      }
    }

    return $commands;
  }

  /**
   * Downloads and unzips the dump.
   *
   * Can be copied from a remote or local destination.
   *
   * @param $filename
   *   The file to be copied.
   *
   * @return string
   *   The absolute path for the dump.
   *
   * @throws CommandException
   */
  protected function getDump($filename) {
    // Sanitise the input file path.
    $filename = $this->cleanFileName($filename);

    // Get the absolute path for local files.
//    if (stream_is_local($filename)) {
//      if (file_exists(realpath($filename))) {
//        $filename = realpath($filename);
//      }
//    }

    // Support for s3.
    if ('s3' === parse_url($filename, PHP_URL_SCHEME)) {
      $filename = $this->s3Copy($filename);
    }
    // Copy from file system or mount.
    else {
      $filename = $this->copy($filename);
    }

    return $filename;
  }

  /**
   * Helper to copy the dump from a source to destination.
   *
   * @param $filename
   *
   * @return string|void The path of the file copied.
   */
  protected function copy($filename) {

    if (!file_exists($filename)) {
      $this->io->comment(sprintf('Dump not found on %s', $filename));
      return;
    }
    $destination = $this->tmpFolder . basename($filename);

    // Check the file isn't already downloaded.
    $this->io->write(sprintf('Checking if db dump needs updating:'));

    if ($this->fileExists($destination) && filesize($filename) === filesize($destination))
    {
      $this->io->comment('No');
    }
    else {
      $this->io->comment('Yes');

      // By default copy() checks if the file has been modified before copying.
      // https://symfony.com/doc/current/components/filesystem.html#copy
      $fs = new Filesystem();

      // Delete the gz and sql files.
      $fs->remove(array($destination, $this->getSqlFilename($destination)));

      // Copy file.
      $fs->copy($filename, $destination);

      // If the file is gzipped we need to unzip it.
      if ($this->isZipped($destination)) {
        $this->unzip($destination);
      }
    }

    if (!$this->fileExists($destination)) {
      $this->io->error(sprintf('Could not copy the dump to %s', $destination));
      return;
    }

    return $this->getSqlFilename($destination);
  }

  /**
   * Helper to copy the dump from a s3 to destination.
   *
   * @param $filename
   *
   * @return string|void The path of the file copied.
   */
  protected function s3Copy($filename) {
    $destination = $this->tmpFolder . basename($filename);

    $command = sprintf(
      'cd %s && ' .
      's3cmd --force --check-md5 get %s',
      $this->tmpFolder,
      $filename
    );

    // @todo Show progress bar.
    $shellProcess = $this->getShellProcess();
    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }

    if (!$this->fileExists($destination)) {
      $this->io->error(sprintf('Could not copy the s3 dump to %s', $destination));
      return;
    }

    // If the file is gzipped we need to unzip it.
    if ($this->isZipped($destination)) {
      $destination = $this->unzip($destination);
    }

    return $this->getSqlFilename($destination);
  }

  /**
   * Checks if the dump is zipped.
   *
   * @param $filename
   *
   * @return bool
   */
  private function isZipped($filename) {
    if (!file_exists($filename)) {
      return;
    }

    if ((function_exists('mime_content_type') &&
        mime_content_type($filename) === 'application/x-gzip') ||
      strpos($filename, '.sql.gz') !== FALSE
    ) {
      return TRUE;
    }
  }

  /**
   * Helper to retrieve the filename.sql
   *
   * @param $filename
   *
   * @return string
   */
  private function getSqlFilename($filename) {
    return rtrim($filename, '.gz');
  }

  /**
   * @param $filename
   *   The zipped file to be extracted.
   *
   * @return string
   *   The unzipped filename.
   *
   * @throws CommandException
   */
  private function unzip($filename) {

    if (!$this->isZipped($filename)) {
      return;
    }

    // Unzip sql file and keep zipped in the folder.
    $command = sprintf(
      'cd %s; ' .
      'gunzip -c %s > %s; ',
      $this->tmpFolder,
      basename($filename),
      $this->getSqlFilename($filename)
    );

    // Run unzip command.
    $this->io->comment(sprintf('Unzipping dump'));

    $shellProcess = $this->getShellProcess();
    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success(sprintf("The DB dump was unzipped as %s", $this->getSqlFilename($filename)));
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }

    return $this->getSqlFilename($filename);
  }

}
