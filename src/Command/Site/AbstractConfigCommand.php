<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\AbstractConfigCommand.
 *
 * Create configuration file from template.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Dflydev\PlaceholderResolver\DataSource\ArrayDataSource;
use Dflydev\PlaceholderResolver\RegexPlaceholderResolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class AbstractConfigCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
abstract class AbstractConfigCommand extends AbstractCommand {

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
      throw new CommandException('::commandName must be set');
    }

    if (empty($this->template)) {
      throw new CommandException('::template must be specified');
    }

    if (empty($this->filename)) {
      throw new CommandException('::filename must be specified');
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
   * @throws \DennisDigital\Drupal\Console\Exception\CommandException
   */
  protected function generateConfigFile() {
    $this->template = $this->getRoot() . $this->template;
    $this->filename = $this->getRoot() . $this->filename;

    // Validation.
    if (!$this->fileExists($this->template)) {
      $message = sprintf('Could not find template %s',
        $this->template
      );
      throw new CommandException($message);
    }

    // Make sure filename doesn't exist.
    if ($this->fileExists($this->filename)) {
      $fs = new Filesystem();
      $fs->remove(array($this->filename));
    }

    $content = $this->fileGetContents($this->template);
    $placeholderMap = $this->getFlatternedConfigArray();
    $placeHolderData = new ArrayDataSource($placeholderMap);
    $placeholderResolver = new RegexPlaceholderResolver($placeHolderData);
    $content = $placeholderResolver->resolvePlaceholder($content);

    $this->filePutContents($this->filename, $content);

    // Check file.
    if ($this->fileExists($this->filename)) {
      $this->io->success(sprintf('Generated %s',
          $this->filename)
      );
    }
    else {
      throw new CommandException(sprintf('Error generating %s',
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
