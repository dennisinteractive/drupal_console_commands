<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\Settings\Command.
 *
 * Creates Local configurations.
 */

namespace DennisDigital\Drupal\Console\Command\Site\Settings;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;
use DennisDigital\Drupal\Console\Command\Site\AbstractCommand;

/**
 * Class Command
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class Command extends AbstractCommand {

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

    $this->setName('site:settings')
      // @todo use: ->setDescription($this->trans('commands.site.settings.local.description'))
      ->setDescription('Generates settings for a given site.');
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

    // Generate settings.php from default.settings.php.
    $this->generateSettingsPhp();

    // Generate settings.local.php.
    $this->generateSettingsLocal();

    // Generate environment specific settings.
    $this->generateSettingsEnv();

    // Run commands with default arguments.
    $commands = array(
      'site:settings:db',
      'site:settings:memcache'
    );

    foreach ($commands as $commandName) {
      $command = $this->getApplication()->find($commandName);

      $parameters = $input->getArguments();
      foreach ($input->getOptions() as $name => $value) {
        $parameters['--' . $name] = $value;
      }
      $commandInput = new ArrayInput(array_filter($parameters));

      $command->run($commandInput, $output);
    }
  }

  /**
   * Generates environment settings.
   *
   * It will look for files on web/sites/[site name] directory that matches
   * default.settings.[env].php.
   *
   * Where:
   * [site name] is the site name
   * [env] is the environment passed as option -e
   *
   * Example:
   * web/sites/example/default.settings.dev.php would get copied to
   * web/sites/example/settings.dev.php.
   *
   * This file should be included via settings.php.
   */
  protected function generateSettingsEnv() {
    $template = sprintf(
      '%sdefault.settings.%s.php',
      $this->getSiteRoot(),
      $this->getEnv()
    );

    $destination = sprintf(
      '%ssettings.%s.php',
      $this->getSiteRoot(),
      $this->getEnv()
    );

    if (file_exists($template)) {
      // Remove existing file.
      if ($this->fileExists($destination)) {
        $this->fileUnlink($destination);
      }

      // Copy the template into web/sites/[site name].
      copy($template, $destination);

      if ($this->fileExists($destination)) {
        $this->io->success(sprintf('Generated %s',
            $destination)
        );
      }
      else {
        throw new CommandException(sprintf('Error generating %s',
            $destination
          )
        );
      }
    }
  }

  /**
   * Generates settings.php from default.settings.php.
   *
   * It will look for files on web/sites/[site name] directory that matches
   * default.settings.php.
   *
   * Example:
   * web/sites/example/default.settings.php would get copied to
   * web/sites/example/settings.php.
   *
   * This file should be included via settings.php.
   */
  protected function generateSettingsPhp() {
    $template = sprintf(
      '%sdefault.settings.php',
      $this->getSiteRoot(),
      $this->getEnv()
    );

    $destination = sprintf(
      '%ssettings.php',
      $this->getSiteRoot(),
      $this->getEnv()
    );

    if (file_exists($template)) {
      // Remove existing file.
      if ($this->fileExists($destination)) {
        $this->fileUnlink($destination);
      }

      // Copy the template into web/sites/[site name].
      copy($template, $destination);

      if ($this->fileExists($destination)) {
        $this->io->success(sprintf('Generated %s',
            $destination)
        );
      }
      else {
        throw new CommandException(sprintf('Error generating %s',
            $destination
          )
        );
      }
    }
  }

  /**
   * Generates settings.local.php.
   *
   * If the site contains a file on web/sites/example.settings.local.php it will
   * make a copy to web/sites/site-name/settings.local.php and replace tokens.
   * If the template doesn't exist it will create one.
   */
  protected function generateSettingsLocal() {

    // Validation.
    $template = $this->getSiteRoot() . '../example.' . $this->filename;
    $file = $this->getSiteRoot() . $this->filename;

    if (!$this->fileExists($template)) {
      $this->io->writeln(sprintf(
        "Cannot find %s.",
          $template
        )
      );
      // Create one.
      $template = '/tmp/example.' . $this->filename;
      $this->filePutContents($template, "<?php\n/**\n * This file was generated automatically.\n*/");
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
    $content = $this->fileGetContents($template) . PHP_EOL . $content;

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
