<?php

namespace Sokil\TaskStockBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('task_stock');

        $rootNode
            ->children()
                ->scalarNode('attachments_filesystem')->end()
                ->arrayNode('stateConfig')
                    ->prototype('array')
                        ->children()
                            ->integerNode('id')->isRequired()->end()
                            ->scalarNode('name')->isRequired()->end()
                            ->arrayNode('states')
                                ->isRequired()
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('label')->isRequired()->end()
                                            ->booleanNode('initial')->defaultValue(false)->end()
                                            ->arrayNode('transitions')
                                                ->isRequired()
                                                    ->prototype('array')
                                                        ->children()
                                                            ->scalarNode('resultingState')->isRequired()->end()
                                                            ->scalarNode('label')->isRequired()->end()
                                                            ->scalarNode('icon')->isRequired()->end()
                                            ->end();

        return $treeBuilder;
    }
}
