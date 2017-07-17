<?php

/**
 * @file
 * Contains \DennisDigital\Drupal\Console\Command\Site\TestCommand.
 *
 * Runs tests.
 */

namespace DennisDigital\Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DennisDigital\Drupal\Console\Command\Exception\CommandException;

/**
 * Class TestCommand
 *
 * @package DennisDigital\Drupal\Console\Command
 */
class TestCommand extends AbstractCommand {

  /**
   * Stores the behat tags.
   *
   * @var string
   */
  protected $behatTags = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setName('site:test')
      ->setDescription('Runs Tests.');

    $this->addArgument(
      'tags',
      InputArgument::REQUIRED,
      'Choose your tags'
    );

  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    $behatTags = $input->getArgument('tags');

    if (!$behatTags) {
      $tagoptions = array_values(array_unique(array_merge(
        ['-n'],
        ['--tags=smoke']
      )));

      $behatTags = $this->io->choice(
        $this->trans('Select your tags'),
        $tagoptions,
        reset($tagoptions),
        TRUE
      );
      $input->setArgument('tags', reset($behatTags));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::execute($input, $output);

    // Validate url.
    $this->validateTags($input);

    $this->io->comment(sprintf('Running Tests on %s',
      $this->getWebRoot()
    ));

    $command = sprintf(
      'cd %stests && ' .
      './behat %s; ' .
      'cd %s; ./vendor/bin/phpunit;',
      $this->shellPath($this->getWebRoot()),
      $this->behatTags,
      $this->shellPath($this->getWebRoot())
    );

    // Run.
    $shellProcess = $this->getShellProcess();

    if ($shellProcess->exec($command, TRUE)) {
      $this->io->writeln($shellProcess->getOutput());
      $this->io->success('Tests Complete');
    }
    else {
      throw new CommandException($shellProcess->getOutput());
    }
  }

  /**
   * Helper to validate repo.
   *
   * @throws CommandException
   *
   * @return string Behat tag(s)
   */
  protected function validateTags(InputInterface $input) {

    $behatTags = $input->getArgument('tags');

    if(!$behatTags) {

      if ($input->hasArgument('tags') &&
        !is_null($input->getArgument('tags'))
      ) {
        $this->behatTags = $input->getArgument('tags');
      }
      else {
        $this->behatTags = '-n';
      }
    }

    $input->setArgument('tags', $behatTags);
    $this->behatTags = $behatTags;

    return $this->behatTags;
  }
}

