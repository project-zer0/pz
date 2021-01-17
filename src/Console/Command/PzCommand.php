<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Console\Command;

use LogicException;
use ProjectZer0\Pz\ProjectZer0Toolkit;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class PzCommand extends Command
{
    protected ?ProjectZer0Toolkit $toolkit = null;
    protected bool $ignoreHelp             = false;

    public function doesIgnoreHelp(): bool
    {
        return $this->ignoreHelp;
    }

    public function ignoreHelp(): void
    {
        $this->ignoreHelp = true;
    }

    public function getConfiguration(): array
    {
        if (null === $this->toolkit) {
            throw new LogicException('Invalid usage of PzCommand');
        }

        return $this->toolkit->getConfiguration();
    }

    public function getLogger(): LoggerInterface
    {
        if (null === $this->toolkit) {
            throw new LogicException('Invalid usage of PzCommand');
        }

        return $this->toolkit->getLogger();
    }

    public function setToolkit(ProjectZer0Toolkit $toolkit): void
    {
        $this->toolkit = $toolkit;
    }
}
