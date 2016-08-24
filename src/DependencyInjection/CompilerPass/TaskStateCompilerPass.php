<?php

namespace Sokil\TaskStockBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser as YamlParser;

class TaskStateCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // get state config
        if ($container->hasParameter('task_stock.state_config')) {
            $stateConfiguration = $container->getParameter('task_stock.state_config');
        } else {
            // parse yaml configs
            $parser = new YamlParser();
            $stateConfiguration = [
                [
                    'id' => 0,
                    'name' => 'Default',
                    'states' => $parser->parse(file_get_contents(__DIR__ . '/../../Resources/config/defaultTaskStates.yml')),
                ]
            ];
        }

        // configure task handler with configs
        $container
            ->findDefinition('task_stock.task_state_handler_builder')
            ->replaceArgument(0, $stateConfiguration);
    }
}