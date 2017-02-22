<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteSettingsMemcacheCommand.
 *
 * Creates Memcache configurations.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteSettingsMemcacheCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteSettingsMemcacheCommand extends SiteBaseCommand {

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename = 'settings.memcache.php';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:settings:memcache')
      // @todo use: ->setDescription($this->trans('commands.site.settings.memcache.description'))
      ->setDescription('Generates settings.memcache.php for a given site.');

      // Add extra options.
      $this->addOption(
        'memcache-prefix',
        '',
        InputOption::VALUE_OPTIONAL,
        // @todo use: $this->trans('commands.site.settings.memcache.prefix')
        'Memcache key prefix. [default: site machine name from sites.yml]'
      );
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
    if (!$this->fileExists($this->destination . 'settings.php')) {
      $message = sprintf('The file settings.php is missing on %s',
        $this->destination
      );
      throw new SiteCommandException($message);
    }

    if (is_null($input->getOption('memcache-prefix'))) {
      $input->setOption('memcache-prefix', $this->siteName . '_' . microtime(true));
    }

    // Remove existing file.
    $file = $this->destination . $this->filename;
    if ($this->fileExists($file)) {
      $this->fileUnlink($file);
    }

    // Prepare content.
    $memcache_prefix = $input->getOption('memcache-prefix');

    $content = <<<EOF
<?php
\$settings['memcache']['servers'] = ['127.0.0.1:11211' => 'default'];
\$settings['memcache']['bins'] = ['default' => 'default'];
\$settings['memcache']['key_prefix'] = '$memcache_prefix';
\$settings['memcache']['stampede_protection'] = TRUE;
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
