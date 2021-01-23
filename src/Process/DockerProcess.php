<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Process;

use LogicException;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class DockerProcess implements ProcessInterface
{
    public const BINARY = '/usr/bin/docker';

    public function __construct(
        protected string $imageName,
        protected array $arguments = [],
        protected bool $detached = false,
        protected bool $interactive = false,
        protected bool $cleanUp = false,
        protected ?string $entrypoint = null,
        protected array $exposePorts = [],
        protected array $volumes = [],
        protected array $envVariables = [],
        protected ?string $name = null,
        protected ?string $workDir = null,
        protected bool $exec = false,
    ) {
    }

    public function detach(): self
    {
        $this->detached = true;

        return $this;
    }

    public function interactive(): self
    {
        $this->interactive = true;

        return $this;
    }

    public function cleanUp(): self
    {
        $this->cleanUp = true;

        return $this;
    }

    public function setEntrypoint(string $entrypoint): self
    {
        $this->entrypoint = $entrypoint;

        return $this;
    }

    public function exposePort(int $local, int $container): self
    {
        $this->exposePorts[] = $local . ':' . $container;

        return $this;
    }

    public function addVolume(string $local, string $container): self
    {
        $this->volumes[] = $local . ':' . $container;

        return $this;
    }

    public function addEnvVariable(string $value): self
    {
        $this->envVariables[] = $value;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setWorkDir(string $workDir): self
    {
        $this->workDir = $workDir;

        return $this;
    }

    public function replaceCurrentProcess(): self
    {
        $this->exec = true;

        return $this;
    }

    public function execute(): int
    {
        $process = $this->getProcess();

        return $process->execute();
    }

    public function getProcess(): Process
    {
        if ($this->exec) {
            throw new LogicException('getProcess cant be used with replaceCurrentProcess');
        }

        $args = [];

        $args[] = 'run';

        if ($this->detached) {
            $args[] = '-d';
        }

        if ($this->interactive) {
            $args[] = '-it';
        }

        if ($this->cleanUp) {
            $args[] = '--rm';
        }

        if (null !== $this->entrypoint) {
            $args[] = '--entrypoint';
            $args[] = $this->entrypoint;
        }

        if (count($this->exposePorts) > 0) {
            foreach ($this->exposePorts as $exposePort) {
                $args[] = '-p';
                $args[] = $exposePort;
            }
        }

        $this->volumes[] = '$PZ_PWD/.pz/docker:/root/.docker';

        foreach ($this->volumes as $volume) {
            $args[] = '-v';
            $args[] = $volume;
        }

        if (count($this->envVariables) > 0) {
            foreach ($this->envVariables as $envVariable) {
                $args[] = '-e';
                $args[] = $envVariable;
            }
        }

        if (null !== $this->name) {
            $args[] = '--name';
            $args[] = $this->name;
        }

        if (null !== $this->workDir) {
            $args[] = '-w';
            $args[] = $this->workDir;
        }

        $args[] = $this->imageName;

        $args = array_merge($args, $this->arguments);

        return new Process(static::BINARY, $args, replaceCurrentProcess: $this->exec);
    }
}
