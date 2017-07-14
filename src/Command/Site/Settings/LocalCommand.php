<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Settings\LocalCommand.
 *
 * Creates Local configurations.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Settings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;
use DennisDigital\Drupal\Console\Command\Site\AbstractCommand;

/**
 * Class LocalCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class LocalCommand extends AbstractCommand {

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename = 'settings.local.php';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:settings:local')
      // @todo use: ->setDescription($this->trans('commands.site.settings.local.description'))
      ->setDescription('Generates settings.local.php for a given site.');
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

    $this->drupal_directory = $this->settingsPhpDirectory();

    // Validation.
    if (!$this->fileExists($this->drupal_directory . '../example.' . $this->filename)) {
      $message = sprintf('The file example.settings.local.php is missing.',
        $this->drupal_directory
      );
      throw new CommandException($message);
    }

    // Remove existing file.
    $file = $this->drupal_directory . $this->filename;
    if ($this->fileExists($file)) {
      $this->fileUnlink($file);
    }

    // Copy example.
    $command = sprintf('cd %s && cp -n ../example.%s %s',
      $this->shellPath($this->drupal_directory),
      $this->filename,
      $this->filename
    );
    $shellProcess = $this->getShellProcess();
    if (!$shellProcess->exec($command, TRUE)) {
      throw new CommandException(sprintf('Error generating %s',
          $this->filename
        )
      );
    }

    // Load the file.
    $content = $this->fileGetContents($file);

    $host= $this->config['host'];
    $cdn = $this->config['cdn'];

    // Append configuration.
    $content .= <<<EOF

// Set Stage file proxy origin.
\$config['stage_file_proxy.settings']['origin'] = '$cdn';

// Change CDN domain to local.
\$config['cdn.settings']['mapping']['domain'] = '$host';

EOF;

    $this->filePutContents($file, $content);

    // Check file.
    if ($this->fileExists($file)) {
      $this->io->success(sprintf('Generated %s',
          $file)
      );
    }
    else {
      throw new CommandException(sprintf('Error generating %s',
          $file
        )
      );
    }
  }
}
