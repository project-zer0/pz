<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Process;

use ErrorException;
use InvalidArgumentException;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class Process implements ProcessInterface
{
    public function __construct(
        protected string $executable,
        protected array $arguments = [],
        protected ?array $environmentVariables = null,
        protected bool $replaceCurrentProcess = false
    ) {
        if (false === file_exists($this->executable) || false === is_executable($this->executable)) {
            throw new InvalidArgumentException('The process executable "' . $this->executable . '" does not exist');
        }
    }

    public function execute(): int
    {
        if ($this->replaceCurrentProcess) {
            if (null !== $this->environmentVariables) {
                pcntl_exec($this->executable, $this->arguments, $this->environmentVariables);
            } else {
                pcntl_exec($this->executable, $this->arguments);
            }

            throw new ErrorException('pcntl_exec failed to start process');
        }

        $process = new SymfonyProcess(
            [$this->executable, ...$this->arguments],
            null,
            $this->environmentVariables
        );

        $process->run(function (string $type, string $buffer): void {
            if (SymfonyProcess::ERR === $type) {
                fwrite(STDERR, $buffer);
            } else {
                fwrite(STDOUT, $buffer);
            }
        });

        return (int) $process->getExitCode();
    }
}
