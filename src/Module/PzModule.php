<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Module;

use ProjectZer0\Pz\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
abstract class PzModule implements PzModuleInterface, PzModuleCommandProviderInterface, EventSubscriberInterface
{
    /**
     * Returns the module console commands to register in console application.
     *
     * @return Command[]
     */
    abstract public function getCommands(): array;

    /**
     * Console application will call this function to register commands.
     */
    public function registerCommands(Application $app): void
    {
        foreach ($this->getCommands() as $command) {
            $app->add($command);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
