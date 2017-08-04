<?php
namespace DennisDigital\Drupal\Console\Utils;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ShellProcess
 * @package Drupal\Console\Utils
 */
class ShellProcess {
  /**
   * @var string
   */
  protected $appRoot;
  /**
   * @var ShellProcess
   */
  private $process;

  /**
   * @var DrupalStyle
   */
  private $io;

  /**
   * Flag to print output.
   *
   * @var bool
   */
  protected $print = TRUE;

  /**
   * Process constructor.
   * @param string $appRoot
   */
  public function __construct($appRoot) {
    $this->appRoot = $appRoot;

    $output = new ConsoleOutput();
    $input = new ArrayInput([]);
    $this->io = new DrupalStyle($input, $output);
  }

  /**
   * Print output.
   *
   * @param bool $print
   * @return $this
   */
  public function printOutput($print = TRUE) {
    $this->print = $print;
    return $this;
  }

  /**
   * @param string $command
   * @param string $workingDirectory
   *
   * @throws ProcessFailedException
   *
   * @return Process
   */
  public function exec($command, $workingDirectory=null) {
    if (!$workingDirectory || $workingDirectory==='') {
      $workingDirectory = $this->appRoot;
    }
    $this->process = new Process($command);
    $this->process->setWorkingDirectory($workingDirectory);
    $this->process->enableOutput();
    $this->process->setTimeout(null);

    if ($this->print) {
      $this->process->run(
        function ($type, $buffer) {
          $this->io->write($buffer);
        }
      );
    }
    else {
      $this->process->run();
    }

    if (!$this->process->isSuccessful()) {
      throw new ProcessFailedException($this->process);
    }

    return $this->process->isSuccessful();
  }

  /**
   * @return string
   */
  public function getOutput() {
    return $this->process->getOutput();
  }
}
