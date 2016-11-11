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
   *
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

    // Inherit arguments and options from SiteCheckoutCommand().
    $command = new SiteCheckoutCommand();
    $this->inheritArguments($command);
    $this->inheritOptions($command);

    // Inherit arguments and options from SiteComposeCommand().
    $command = new SiteComposeCommand();
    $this->inheritArguments($command);
    $this->inheritOptions($command);

    // Inherit arguments and options from SiteSettingsDbCommand().
    $command = new SiteSettingsDbCommand();
    $this->inheritArguments($command);
    $this->inheritOptions($command);

    // Inherit arguments and options from SiteSettingsMemcacheCommand().
    $command = new SiteSettingsMemcacheCommand();
    $this->inheritArguments($command);
    $this->inheritOptions($command);
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

    $profile = ($input->getArgument('profile')) ? $input->getArgument('profile') : 'minimal';
    $siteName = $input->getArgument('site-name');

    $this->io->writeln(sprintf(
        'Building %s using %s profile',
        $siteName,
        $profile
      )
    );

    $command = sprintf(
      'drupal chain --file /vagrant/repos/drupal_console_commands/config/chain/chain-site-install.yml ' .
      '--placeholder="site_name:%s" --placeholder="profile:%s"',
      $siteName,
      $profile
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
