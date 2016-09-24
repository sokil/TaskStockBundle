<?php

namespace Sokil\TaskStockBundle\DependencyInjection;

use Sokil\Diff\Renderer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class TaskStockExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // services
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // attachments
        if (isset($config['attachments_filesystem'])) {
            $container->setParameter(
                $this->getAlias() . '.attachments_filesystem',
                $config['attachments_filesystem']
            );
        }

        // state
        if (isset($config['stateConfig'])) {
            $container->setParameter('task_stock.state_config', $config['stateConfig']);
        }

        // diff renderer formats
        $container->setParameter('task_stock.diff_renderer.format_colour', Renderer::FORMAT_COLOUR);
    }
}
