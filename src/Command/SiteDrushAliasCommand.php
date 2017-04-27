<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteDrushAliasCommand.
 *
 * Create Drush Alias for the site.
 */

namespace DennisDigital\Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;
use DennisDigital\Drupal\Console\Command\Shared\SiteInstallArgumentsTrait;

/**
 * Class SiteDrushAliasCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteDrushAliasCommand extends SiteBaseCommand {

  use SiteInstallArgumentsTrait;

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename = 'default.aliases.drush9rc.php';

  /**
   * The dir folder to save the alias to.
   *
   * @var
   */
  protected $dir = 'web/sites/all/drush/site-aliases/';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:drush:alias')
      ->setDescription('Generates the drush aliases required.');

    // Re-use SiteInstall options and arguments.
    $this->getSiteInstallArguments();
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

    // Remove existing file.
    $file = $this->destination . $this->dir . $this->filename;
    if ($this->fileExists($file)) {
      $this->fileUnlink($file);
    }

    $content = <<<EOF
<?php
/**
 * Drush alias for site {$this->siteName}.
 */
\$aliases["site"] = array (
  'user' => 1,
);
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
