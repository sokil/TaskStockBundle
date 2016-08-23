<?php

namespace Sokil\TaskStockBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NotificationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $notificationMessageBuilder = $container->findDefinition('notification.message_builder');
        $notificationMessageBuilder->addMethodCall(
            'registerMessageTypes',
            [
                [
                    'taskChange' => [
                        'email' => $container->getParameter('notification.message.email.taskChange.class'),
                    ],
                ],
            ]
        );
    }
}