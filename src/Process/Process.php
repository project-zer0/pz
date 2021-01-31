<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Process;

use ErrorException;
use InvalidArgumentException;
use LogicException;
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
        $args = $this->arguments;
        array_unshift($args, $this->executable);
        $args = implode(' ', $args);

        if ($this->replaceCurrentProcess) {
            if (null !== $this->environmentVariables) {
                pcntl_exec('/bin/sh', ['-c', $args], $this->environmentVariables);
            } else {
                pcntl_exec('/bin/sh', ['-c', $args]);
            }

            throw new ErrorException('pcntl_exec failed to start process');
        }

        return $this->getProcess()
            ->setTimeout(null)
            ->setTty(true)
            ->run();
    }

    public function getProcess(): SymfonyProcess
    {
        if ($this->replaceCurrentProcess) {
            throw new LogicException('getProcess cant be used with replaceCurrentProcess');
        }

        $args = $this->arguments;
        array_unshift($args, $this->executable);
        $args = implode(' ', $args);

        return new SymfonyProcess(
            ['/bin/sh', '-c', $args],
            null,
            $this->environmentVariables
        );
    }
}
