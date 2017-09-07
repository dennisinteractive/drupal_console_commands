<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Settings\LocalCommand.
 *
 * Creates Local configurations.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Settings;

use Symfony\Component\Console\Input\InputOption;
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
  protected $source = '../example.settings.local.php';
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
   * Generates settings.local.php.
   *
   * If the site contains a file on web/sites/example.settings.local.php it will
   * make a copy to web/sites/site-name/settings.local.php and replace tokens.
   * For Drupal 7, it will look for a file called default.settings.local.php.
   * If the template doesn't exist it will create one.
   *
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    if ($this->getDrupalVersion() === 7) {
      $this->source = 'default.settings.local.php';
    }

    // Validation.
    $source = $this->getSiteRoot() . $this->source;
    $file = $this->getSiteRoot() . $this->filename;

    if (!$this->fileExists($source)) {
      $this->io->writeln(sprintf(
          "Cannot find %s.",
          $source
        )
      );
      // Create one.
      $source = '/tmp/' . $this->filename;
      $this->filePutContents($source, "<?php\n/**\n * This file was generated automatically.\n*/");
    }

    // Remove existing file.
    if ($this->fileExists($file)) {
      $this->fileUnlink($file);
    }

    $host = isset($this->config['host']) ? $this->config['host'] : '';
    $cdn = isset($this->config['cdn']) ? $this->config['cdn'] : '';

    // Load from template.
    $content = $this->loadTemplate(__FILE__, $this->filename);

    // Replace tokens.
    $content = str_replace('<?php', '', $content);
    $content = str_replace('${cdn}', $cdn, $content);
    $content = str_replace('${host}', $host, $content);

    // Prepend example file.
    $content = $this->fileGetContents($source) . PHP_EOL . $content;

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
