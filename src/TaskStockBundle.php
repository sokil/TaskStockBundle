<?php

namespace Sokil\TaskStockBundle;

use Sokil\TaskStockBundle\DependencyInjection\CompilerPass\NotificationCompilerPass;
use Sokil\TaskStockBundle\DependencyInjection\CompilerPass\TaskStateCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TaskStockBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new NotificationCompilerPass());
        $container->addCompilerPass(new TaskStateCompilerPass());
    }
}
