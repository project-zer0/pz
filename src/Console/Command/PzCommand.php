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

    public function ignoreHelp(): self
    {
        $this->ignoreHelp = true;

        return $this;
    }

    public function getConfiguration(): array
    {
        return $this->getToolkit()->getConfiguration();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->getToolkit()->getLogger();
    }

    public function setToolkit(ProjectZer0Toolkit $toolkit): void
    {
        $this->toolkit = $toolkit;
    }

    public function getToolkit(): ProjectZer0Toolkit
    {
        if (null === $this->toolkit) {
            throw new LogicException('Invalid usage of PzCommand');
        }

        return $this->toolkit;
    }
}
