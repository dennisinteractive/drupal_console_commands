<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteSettingsLocalCommand.
 *
 * Creates Local configurations.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteSettingsLocalCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteSettingsLocalCommand extends SiteBaseCommand {

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

    $this->destination = $this->settingsPhpDirectory();

    // Validation.
    if (!$this->fileExists($this->destination . '../example.' . $this->filename)) {
      $message = sprintf('The file example.settings.local.php is missing.',
        $this->destination
      );
      throw new SiteCommandException($message);
    }

    // Remove existing file.
    $file = $this->destination . $this->filename;
    if ($this->fileExists($file)) {
      $this->fileUnlink($file);
    }

    // Copy example.
    $command = sprintf('cd %s && cp -n ../example.%s %s',
      $this->shellPath($this->destination),
      $this->filename,
      $this->filename
    );
    $shellProcess = $this->getShellProcess();
    if (!$shellProcess->exec($command, TRUE)) {
      throw new SiteCommandException(sprintf('Error generating %s',
          $this->filename
        )
      );
    }

    // Load the file.
    $content = $this->fileGetContents($file);

    // Append configuration.
    $content .= <<<EOF

// Set Stage file proxy origin.
\$config['stage_file_proxy.settings']['origin'] = 'cdn.subscriptions.dennis.co.uk';

// Change CDN domain to local.
\$config['cdn.settings']['mapping']['domain'] = 'subscriptions.vm8.didev.co.uk';

EOF;

    $this->filePutContents($file, $content);

    // Check file.
    if ($this->fileExists($file)) {
      $this->io->success(sprintf('Generated %s',
          $file)
      );
    }
    else {
      throw new SiteCommandException(sprintf('Error generating %s',
          $file
        )
      );
    }
  }
}
