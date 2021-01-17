<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Config;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
interface PzModuleConfigurationInterface
{
    public function getConfigurationNode(): NodeDefinition;
}
