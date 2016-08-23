<?php

namespace Sokil\TaskStockBundle\EventListener;

use Sokil\NotificationBundle\MessageBuilder;
use Sokil\NotificationBundle\Exception\NotificationException;
use Sokil\NotificationBundle\Schema\ConfigurationProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sokil\TaskStockBundle\Event\TaskChangeEvent;
use Sokil\NotificationBundle\TransportProvider;

class TaskNotificationListener implements EventSubscriberInterface
{
    /**
     * @var ConfigurationProvider
     */
    private $schemaConfigurationProvider;

    private $transportProvider;

    private $messageBuilder;

    public function __construct(
        ConfigurationProvider $schemaProvider,
        TransportProvider $transportProvider,
        MessageBuilder $messageBuilder
    ) {
        $this->schemaConfigurationProvider = $schemaProvider;
        $this->transportProvider = $transportProvider;
        $this->messageBuilder = $messageBuilder;
    }

    public function onTaskChange(TaskChangeEvent $event)
    {
        $tasks = $event->getTasks();
        $user = $event->getUser();

        foreach($event->getChanges() as $taskId => $changes) {
            // get task
            $task = $tasks[$taskId];
            $project = $task->getProject();

            // get notification config
            $schemaId = $project->getNotificationSchemaId();
            if (null === $schemaId) {
                return;
            }

            // send
            $schema = $this->schemaConfigurationProvider->getConfiguration($schemaId);
            foreach($schema->getRecipients() as $transportName => $recipients) {
                // recipients
                if ($transportName == 'email') {
                    if (in_array('creator', $recipients)) {
                        $recipients[] = $task->getOwner()->getEmail();
                    }
                    if (in_array('assignee', $recipients)) {
                        $recipients[] = $task->getAssignee()->getEmail();
                    }
                    if (in_array('watchers', $recipients)) {
                        //TODO: not implemented
                    }
                    // remove email of user that do changes
                    if (in_array($user->getEmail(), $recipients)) {
                        unset($recipients[array_search($user->getEmail(), $recipients)]);
                    }
                }

                unset($recipients[array_search('creator', $recipients)]);
                unset($recipients[array_search('assignee', $recipients)]);
                unset($recipients[array_search('watchers', $recipients)]);
                $recipients = array_unique($recipients);

                // message
                $message = $this->messageBuilder->createMessage('taskChange', $transportName);
                $message
                    ->setTask($task)
                    ->setUser($user)
                    ->setChanges($changes);

                try {
                    $this
                        ->transportProvider
                        ->getTransport($transportName)
                        ->send(
                            $message,
                            $recipients
                        );
                } catch (NotificationException $e) {}
            }

        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'task.change' => 'onTaskChange',
        ];
    }
}
