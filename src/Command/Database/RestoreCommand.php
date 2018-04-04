<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Database\RestoreCommand.
 * This is a temporary file until this PR is available https://github.com/hechoendrupal/drupal-console/pull/3856
 */

namespace DennisDigital\Drupal\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Command\Shared\ConnectTrait;

class RestoreCommand extends Command
{
    use ConnectTrait;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * RestoreCommand constructor.
     *
     * @param string $appRoot
     */
    public function __construct($appRoot)
    {
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:restore')
            ->setDescription($this->trans('commands.database.restore.description'))
            ->addArgument(
                'database',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.restore.arguments.database'),
                'default'
            )
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.database.restore.options.file')
            )
            ->setHelp($this->trans('commands.database.restore.help'))
            ->setAliases(['dbr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $database = $input->getArgument('database');
        $file = $input->getOption('file');
        $learning = $input->getOption('learning');

        $databaseConnection = $this->resolveConnection($database);

        if (!$file) {
            $this->getIo()->error(
                $this->trans('commands.database.restore.messages.no-file')
            );
            return 1;
        }

        if (strpos($file, '.sql.gz') !== false) {
            $catCommand = "gunzip -c %s | ";
        } else {
            $catCommand = "cat %s | ";
        }

        $commands = array();
        if ($databaseConnection['driver'] == 'mysql') {
          // Drop database first.
          $commands[] = sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%s -e"DROP DATABASE IF EXISTS %s"',
            $databaseConnection['username'],
            $databaseConnection['password'],
            $databaseConnection['host'],
            $databaseConnection['port'],
            $databaseConnection['database']
          );

          // Recreate database.
          $commands[] = sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%s -e"CREATE DATABASE %s"',
            $databaseConnection['username'],
            $databaseConnection['password'],
            $databaseConnection['host'],
            $databaseConnection['port'],
            $databaseConnection['database']
          );

          // Import dump.
          $commands[] = sprintf(
                $catCommand . 'mysql --user=%s --password=%s --host=%s --port=%s %s',
                $file,
                $databaseConnection['username'],
                $databaseConnection['password'],
                $databaseConnection['host'],
                $databaseConnection['port'],
                $databaseConnection['database']
            );
        } elseif ($databaseConnection['driver'] == 'pgsql') {
            $commands[] = sprintf(
                'PGPASSWORD="%s" ' . $catCommand . 'psql -w -U %s -h %s -p %s -d %s',
                $file,
                $databaseConnection['password'],
                $databaseConnection['username'],
                $databaseConnection['host'],
                $databaseConnection['port'],
                $databaseConnection['database']
            );
        }

        foreach ($commands as $command) {
            if ($learning) {
                $this->getIo()->commentBlock($command);
            }

            $processBuilder = new ProcessBuilder(['-v']);
            $process = $processBuilder->getProcess();
            $process->setWorkingDirectory($this->appRoot);
            $process->setTty($input->isInteractive());
            $process->setCommandLine($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }
        }

        $this->getIo()->success(
            sprintf(
              '%s %s',
              $this->trans('commands.database.restore.messages.success'),
              $file
            )
        );

        return 0;
    }
}
