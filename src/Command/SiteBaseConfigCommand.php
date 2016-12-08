<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\SiteBaseConfigCommand.
 *
 * Create configuration file from template.
 */

namespace DennisDigital\Drupal\Console\Command;

use Dflydev\PlaceholderResolver\DataSource\ArrayDataSource;
use Dflydev\PlaceholderResolver\RegexPlaceholderResolver;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Drupal\Console\Command\Site\InstallCommand;
use DennisDigital\Drupal\Console\Exception\SiteCommandException;

/**
 * Class SiteBaseConfigCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class SiteBaseConfigCommand extends SiteBaseCommand {

  /**
   * Config that is generated at runtime.
   *
   * @var array
   */
  protected $runtimeConfig = array();

  /**
   * The file name to generate.
   *
   * @var
   */
  protected $filename;

  /**
   * The template.
   *
   * @var
   */
  protected $template;

  /**
   * The console command.
   *
   * @var
   */
  protected $commandName;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    if (empty($this->commandName)) {
      throw new SiteCommandException('::commandName must be set');
    }

    if (empty($this->template)) {
      throw new SiteCommandException('::template must be specified');
    }

    if (empty($this->filename)) {
      throw new SiteCommandException('::filename must be specified');
    }

    $this->setName($this->commandName)
      ->setDescription('Generates ' . $this->filename . ' form a template.');

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
    // Generate config file from template.
    $this->generateConfigFile();
  }

  /**
   * Generates config file from template.
   *
   * @throws \DennisDigital\Drupal\Console\Exception\SiteCommandException
   */
  protected function generateConfigFile() {
    $this->template = $this->destination . $this->template;
    $this->filename = $this->destination . $this->filename;

    // Validation.
    if (!file_exists($this->template)) {
      $message = sprintf('Could not find %s',
        $this->template
      );
      throw new SiteCommandException($message);
    }

    // Make sure filename doesn't exist.
    if (file_exists($this->filename)) {
      $fs = new Filesystem();
      $fs->remove(array($this->filename));
    }

    $content = file_get_contents($this->template);
    $placeholderMap = $this->getFlatternedConfigArray();
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

  /**
   * Returns config flatten in dot notation.
   *
   * @return array
   */
  protected function getFlatternedConfigArray() {
    $config = array_merge_recursive($this->config, $this->runtimeConfig);
    $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($config));
    $flattened = array();
    foreach ($ritit as $leafValue) {
      $keys = array();
      foreach (range(0, $ritit->getDepth()) as $depth) {
        $keys[] = $ritit->getSubIterator($depth)->key();
      }
      $flattened[ join('.', $keys) ] = $leafValue;
    }
    return $flattened;
  }
}
