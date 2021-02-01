<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Config;

use ProjectZer0\Pz\Module\PzModuleInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class Configuration implements ConfigurationInterface
{
    /** @var PzModuleInterface[] */
    private array $modules;

    /**
     * @param PzModuleInterface[] $modules
     */
    public function __construct(array $modules)
    {
        $this->modules = $modules;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('project_zer0');

        $rootNode = $treeBuilder
            ->getRootNode()
            ->children();

        $rootNode->scalarNode('launcher_docker_image')
            ->defaultValue('projectzer0/pz-launcher')
            ->end();

        foreach ($this->modules as $module) {
            $node = $module->getConfiguration();

            if (null === $node) {
                continue;
            }

            $rootNode->append($node->getConfigurationNode());
        }

        return $treeBuilder;
    }
}
