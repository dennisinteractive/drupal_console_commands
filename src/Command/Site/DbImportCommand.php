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
use DennisDigital\Drupal\Console\Command\Shared\SiteInstallArgumentsTrait;

/**
 * Class DbImportCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class DbImportCommand extends AbstractCommand {
  use SiteInstallArgumentsTrait;

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

    // If we're installing from a dump that's not already in
    // our local destination, copy it to our local destination.
    if (!empty($this->filename)) {
      $this->filename = $this->copy($this->filename);

      // If the file is gzipped we need to unzip it.
      $this->filename = $this->unzip($this->filename);
    }

    $this->destination = $this->settingsPhpDirectory();

    // Override default values for these options (if empty).
    $override = array(
      'account-name' => $this->config['account-name'],
      'account-pass' => $this->config['account-pass'],
      'account-mail' => $this->config['account-mail'],
    );
    foreach ($this->getDefinition()->getOptions() as $option) {
      $name = $option->getName();
      if (array_key_exists($name, $override) && is_null($input->getOption($name))) {
        $input->setOption($name, $override[$name]);
      }
    }

    // Populate options.
    $options = '';
    foreach ($this->getDefinition()->getOptions() as $option) {
      $name = $option->getName();
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

    // If a dump file wasn't found or not specified, do a fresh site install
    if (empty($this->filename) || !$this->fileExists($this->filename)) {
      //@todo Use drupal site:install instead of Drush.
      $command = sprintf(
        'cd %s && ' .
        'chmod 777 ../default && ' .
        'chmod 777 settings.php && ' .
        'drush si -y %s %s && ' .
        'drush cset "system.site" uuid "$(drush cget system.site uuid --source=sync --format=list)" -y',
        $this->shellPath($this->destination),
        $this->profile,
        $options
      );
      $this->io->comment('Installing site');
    }
    else {
      if (is_null($input->getOption('db-name'))) {
        $input->setOption('db-name', $this->siteName);
      }
      $command = sprintf(
        'cd %s; ' .
        'drush sql-create %s -y; ' .
        'drush sql-cli < %s; ',
        $this->destination,
        $input->getOption('db-name'),
        $this->filename
      );
      $this->io->comment('Importing dump');
    }

    $this->io->commentBlock($command);

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success(sprintf(
        "Site installed on %s\nURL %s",
        $this->destination,
        $this->config['host']
      ));
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }

  }

  /**
   * Downloads or copies the dump to a local destination.
   *
   * Can be copied from a remote or local desintaiton.
   *
   * @param $filename
   *   The file to be copied.
   *
   * @return string
   *   The absolute path for the dump.
   *
   * @throws CommandException
   */
  protected function copy($filename) {
    // Sanitise the input file path.
    $filename = $this->cleanFileName($filename);
    $basename = basename($filename);

    // Return canonicalized absolute pathname for local files.
    if (stream_is_local($filename)) {
      $filename = realpath($filename);
    }

    // Check we're not explicitly using a file in the local destination.
    if (substr($filename, 0, strlen($this->tmpFolder)) === $this->tmpFolder) {
      return $filename;
    }

    // Save the db dump from S3 to the local destination.
    // We use S3cmd because the stream_wrapper isn't authenticated
    // and because it validates the checksum for us.
    if ('s3' === parse_url($filename, PHP_URL_SCHEME)) {
      $command = sprintf(
        'cd %s && ' .
        's3cmd --force --check-md5 get %s',
        $this->tmpFolder,
        $filename
      );

      $shellProcess = $this->getShellProcess();
      if ($shellProcess->exec($command, TRUE)) {
        $this->io->writeln($shellProcess->getOutput());
      }
      else {
        throw new CommandException($shellProcess->getOutput());
      }
    }
    // For all other methods including remote and local files
    // we copy chunk by chunk to the local destination.
    else {
      // Check the file isn't already downloaded.
      $this->io->write(sprintf('Checking if db dump needs updating:'));
      if ($this->fileExists($this->tmpFolder . $basename) && md5_file($this->tmpFolder . $basename) === md5_file($filename)) {
        $this->io->comment('No');
      }
      else {
        $this->io->comment('Yes');
        // Open a stream in read-only mode
        if ($this->fileExists($filename) && $stream = fopen($filename, 'r')) {
          // Open the destination file to save the output to.
          $output_file = fopen($this->tmpFolder . $basename, "a");
          if ($output_file) {
            while (!feof($stream)) {
              fwrite($output_file, fread($stream, 1024), 1024);
            }
          }
          fclose($stream);
          if ($output_file) {
            fclose($output_file);
          }
        }
      }
    }

    // Final check to see if copy was successful.
    if (!$this->fileExists($this->tmpFolder . $basename)) {
      $this->io->error(sprintf('Could not copy the dump to %s', $this->tmpFolder . $basename));

      return FALSE;
    }

    return $this->tmpFolder . $basename;
  }

  /**
   * @param $filename
   *   The zipped file to be extracted.
   *
   * @return string
   *   The unzipped file.
   *
   * @throws CommandException
   */
  public function unzip($filename) {
    if (!$this->fileExists($filename)) {
      return FALSE;
    }

    // The basename without any path.
    $baseNameGz = basename($filename);
    // The basename with sql extension.
    $baseNameSql = rtrim($baseNameGz, '.gz');

    // Unzip sql file and keep zipped in the folder.
    if (mime_content_type($this->filename) === 'application/x-gzip') {
      $command = sprintf(
        'cd %s; ' .
        'gunzip -c %s > %s; ',
        $this->tmpFolder,
        $baseNameGz,
        $baseNameSql
      );
    }
    // Return the file without modification.
    else {
      return $filename;
    }

    // Run unzip command.
    $shellProcess = $this->getShellProcess();
    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success(sprintf("The DB dump was unzipped to %s", $this->tmpFolder . $baseNameSql));
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }

    // Use the file extracted on tmp folder.
    return $this->tmpFolder . $baseNameSql;
  }

}
