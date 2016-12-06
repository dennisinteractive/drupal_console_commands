<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteBehatSetupCommand.
 *
 * Create behat.yml from template.
 */

namespace VM\Console\Command\Develop;

use Dflydev\PlaceholderResolver\DataSource\ArrayDataSource;
use Dflydev\PlaceholderResolver\RegexPlaceholderResolver;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Drupal\Console\Command\Site\InstallCommand;
use VM\Console\Command\Exception\SiteCommandException;

/**
 * Class SiteBehatSetupCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteBehatSetupCommand extends SiteBaseCommand {

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename = 'tests/behat.yml';

  /**
   * The template.
   *
   * @var
   */
  protected $template = 'tests/behat.yml.dist';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:behat:setup')
      ->setDescription('Generates behat.yml form a template.');

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

    $this->template = $this->destination . $this->template;
    $this->filename = $this->destination . $this->filename;

    print_r($this->config);
    // Validation.
    if (!file_exists($this->template)) {
      $message = sprintf('Could not find %s',
        $this->template
      );
      throw new SiteCommandException($message);
    }

    // Make sure behat.yml doesn't exist.
    if (file_exists($this->filename)) {
      $fs = new Filesystem();
      $fs->remove(array($this->filename));
    }

    $content = file_get_contents($this->template);

    $placeholderMap = $this->config;
    $placeHolderData = new ArrayDataSource($placeholderMap);
    $placeholderResolver = new RegexPlaceholderResolver($placeHolderData);
    $content = $placeholderResolver->resolvePlaceholder($content);

    file_put_contents($this->filename, $content);

    // Check file.
    if (file_exists($this->filename)) {
      $this->io->success(sprintf('Generated %s',
          $this->filename)
      );
    }
    else {
      throw new SiteCommandException(sprintf('Error generating %s',
        $this->filename
        )
      );
    }
  }
}
