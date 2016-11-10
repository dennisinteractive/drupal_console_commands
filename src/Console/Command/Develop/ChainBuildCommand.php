<?php

/**
 * @file
 * Contains \VM\Console\Develop\ChainBuildCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Application;
use Drupal\Console\Utils\ConfigurationManager;
use Drupal\Console\Config;
/**
 * Class ChainBuildCommand
 *
 * @package VM\Console\Command\Develop
 */
class ChainBuildCommand extends SiteBaseCommand {

  /**
   * Stores the list of chain commands.
   * @var null
   */
  protected $chainData = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    //parent::configure();

    $this->setName('chain:build')
      ->setDescription('Runs the chain:build (custom chain).');

    // Load chain arguments and options for each command.
    $this->_getChainConfig('chain-build.yml');

    // Stores the values to be passed as arguments.
    //$commandArguments = [];
    // Stores the values to be passed as options.
    //$commandOptions = [];

    // Populate the arguments and options form the yml and make them available
    // to the chain command.
    foreach ($this->chainData['commands'] as $item) {
      // Populate arguments.
      $arguments = $this->getDefinition()->getArguments();
      if (isset($item['arguments'])) {
        foreach ($item['arguments'] as $name => $value) {
          if (!isset($arguments[$name])) {
            $this->addArgument($name, InputOption::VALUE_REQUIRED);
            //$commandArguments[$item['command']][$name] = $value;
          }
        }
      }

      // Populate options.
      $options = $this->getDefinition()->getOptions();
      if (isset($item['options'])) {
        foreach ($item['options'] as $name => $value) {
          if (!isset($options[$name])) {
            $this->addOption($name, NULL, InputOption::VALUE_OPTIONAL);
            //$commandOptions[$item['command']][$name] = $value;
          }
        }
      }
    }

  //  var_dump($this->getDefinition()->getArguments());
  // var_dump($commandArguments);
  //  var_dump($this->getDefinition()->getOptions());
  //  var_dump($commandOptions);
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
    foreach ($this->chainData['commands'] as $item) {

      // Gather arguments.
      $arguments = [];
      if (isset($item['arguments'])) {
        foreach ($item['arguments'] as $name => $value) {
          if (!is_null($input->getArgument($name))) {
            $value = $input->getArgument($name);
          }
          if (!empty($value)) {
            $arguments[] = sprintf('%s',
              $value
            );
          }
        }
      }

      // Gather options.
      $options = [];
      if (isset($item['options'])) {
        foreach ($item['options'] as $name => $value) {
          if (!is_null($input->getOption($name))) {
            $value= $input->getOption($name);
          }
          if (!empty($value)) {
            $options[] = sprintf('--%s %s',
              $name,
              $value
            );
          }
        }
      }

      // Build command.
      $command = sprintf('drupal %s %s %s',
        $item['command'],
        implode(' ', $arguments),
        implode(' ', $options)
      );

      $this->io->commentBlock($command);

      // Run.
      $shellProcess = $this->get('shell_process');

      if ($shellProcess->exec($command, TRUE)) {
        $this->io->success('Done');
      }
      else {
        throw new SiteCommandException($shellProcess->getOutput());
      }
    }
  }

  /**
   * Helper to check that the config file exits.
   *
   * @param $file The file name.
   *
   * @return The contents of the config file.
   */
  protected function _getChainConfig($configFile) {
    $ymlFile = new Parser();
    $config = new Config($ymlFile);

    $configFile = realpath(__DIR__ . '/../../../../config/chain/' . $configFile);
    if (!file_exists($configFile)) {
      return [];
    }

    $this->chainData = $config->getFileContents($configFile);

    return $this;
  }
}
