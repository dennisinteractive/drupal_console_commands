<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteDbImportCommand.
 *
 * Imports local dumps or installs a fresh site if no dump is found.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Aws\S3\S3Client;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;
use DennisDigital\Drupal\Console\Command\Shared\SiteInstallArgumentsTrait;

/**
 * Class SiteDbImportCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteDbImportCommand extends SiteBaseCommand {
  use SiteInstallArgumentsTrait;

  /**
   * The Db dump file.
   *
   * @var
   */
  protected $filename = NULL;

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
    if ($input->hasOption('file') &&
      !is_null($input->getOption('file'))
    ) {
      $this->filename = $input->getOption('file');
    }
    elseif (isset($this->config['db']['dump'])) {
      // Use config from sites.yml.
      $this->filename = $this->config['db']['dump'];
    }
    else {
      throw new SiteCommandException('Please specify a file to import the dump from');
    }

    // If the db dump is not local, download it locally.
    if (!stream_is_local($this->filename)) {
      $this->filename = $this->download($this->filename);
    }

    // Check if the file exits.
    if (!$this->is_absolute_path($this->filename)) {
      $filename = realpath(getcwd() . trim($this->filename, '.'));
      if (!$this->fileExists($filename)) {
        throw new SiteCommandException(sprintf('Dump file %s doesn\'t exist',
            $this->filename)
        );
      }
      else {
        $this->filename = $filename;
      }
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
    if (!$this->fileExists($this->filename)) {
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
      $command = '';
      // Check the format of the dump.
      switch (mime_content_type($this->filename)) {
        case 'application/x-gzip':

          // The basename without any path.
          $baseNameGz = basename($this->filename);

          // The basename with sql extension.
          $baseNameSql = str_replace('.sql.gz', '.sql', $baseNameGz);

          $this->io->write(sprintf('Checking if dump exists locally: '));
          if ($this->fileExists('/tmp/' . $baseNameGz)) {
            $this->io->writeln('Yes');
          }
          else {
            $this->io->writeln('No');
          }

          $this->io->write(sprintf('Checking if local dump is up to date: '));
          if (filesize('/tmp/' . $baseNameGz) != filesize($this->filename)) {
            $this->io->writeln('No');

            // Copy file to /tmp.
            $command .= sprintf(
              'cp -L %s /tmp; ',
              $this->filename
            );
          }
          else {
            $this->io->writeln('Yes');
          }

          // Unzip sql file and keep zipped in the folder.
          $command .= sprintf(
            'cd /tmp; ' .
            'gunzip -c %s > %s; ',
            $baseNameGz,
            $baseNameSql
          );

          // Use the file extracted on tmp folder.
          $this->filename = '/tmp/' . $baseNameSql;
          break;
      }

      if (is_null($input->getOption('db-name'))) {
        $input->setOption('db-name', $this->siteName);
      }
      $command .= sprintf(
        'cd %s; ' .
        'drush sql-create -y; ' .
        'drush sql-cli < %s; ',
        $this->destination,
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
      throw new SiteCommandException($shellProcess->getOutput());
    }

  }

  /**
   * Downloads the dump from s3 bucket.
   *
   * @param $filename
   *
   * @return string The absolute path for the dump.
   *
   * @throws SiteCommandException
   */
  protected function download($filename) {
    $tmp_folder = '/tmp/';

    // Save the dbdump to a local destination.
    if ('s3' === parse_url($filename, PHP_URL_SCHEME)) {
      $command = sprintf(
        'cd %s && ' .
        's3cmd --force get %s',
        $tmp_folder,
        $filename
      );

      $shellProcess = $this->getShellProcess();
      if ($shellProcess->exec($command, TRUE)) {
        $this->io->writeln($shellProcess->getOutput());
      }
      else {
        throw new SiteCommandException($shellProcess->getOutput());
      }
    }
    else {
      // Open a stream in read-only mode
      if ($stream = fopen($filename, 'r')) {
        // Open the destination file to save the output to.
        $output_file = fopen ($tmp_folder . basename($filename), "a");
        if ($output_file)
          while(!feof($stream)) {
            fwrite($output_file, fread($stream, 1024), 1024);
          }
        fclose($stream);
        if ($output_file) {
          fclose($output_file);
        }
      }
      else {
        throw new SiteCommandException("The DB dump could not be saved to the local destination.");
      }
    }

    return $tmp_folder . basename($filename);
  }

}
