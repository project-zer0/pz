<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Module;

use ProjectZer0\Pz\Config\PzModuleConfigurationInterface;
use ProjectZer0\Pz\ProjectZer0Toolkit;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
interface PzModuleInterface
{
    /**
     * Boots the module.
     */
    public function boot(ProjectZer0Toolkit $toolkit): void;

    /**
     * Returns the module configuration that should be supported in `.pz.(yaml|xml|json)` file.
     */
    public function getConfiguration(): ?PzModuleConfigurationInterface;

    /**
     * Returns the module name (the class short name).
     *
     * @return string The Module name
     */
    public function getName(): string;
}
