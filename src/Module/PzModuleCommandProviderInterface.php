<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Module;

use ProjectZer0\Pz\Console\Application;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
interface PzModuleCommandProviderInterface
{
    /**
     * Console application will call this function to register commands.
     */
    public function registerCommands(Application $app): void;
}
