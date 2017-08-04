<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Settings\MemcacheCommand.
 *
 * Creates Memcache configurations.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Settings;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;
use DennisDigital\Drupal\Console\Command\Site\AbstractCommand;

/**
 * Class MemcacheCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class MemcacheCommand extends AbstractCommand {

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

    // Validation.
    if (!$this->fileExists($this->getSiteRoot() . 'settings.php')) {
      $message = sprintf('The file settings.php is missing on %s',
        $this->getSiteRoot()
      );
      throw new CommandException($message);
    }

    if (is_null($input->getOption('memcache-prefix'))) {
      $input->setOption('memcache-prefix', $this->siteName . '_' . microtime(true));
    }

    // Remove existing file.
    $file = $this->getSiteRoot() . $this->filename;
    if ($this->fileExists($file)) {
      $this->fileUnlink($file);
    }

    // Prepare content.
    $memcache_prefix = $input->getOption('memcache-prefix');

    // Load from template.
    $content = $this->loadTemplate(__FILE__, $this->filename);

    // Replace tokens.
    $content = str_replace('${memcache_prefix}', $memcache_prefix, $content);

    // Write file.
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
