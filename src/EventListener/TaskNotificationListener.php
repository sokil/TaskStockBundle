<?php

namespace Sokil\TaskStockBundle\EventListener;

use Sokil\NotificationBundle\Exception\NotificationException;
use Sokil\NotificationBundle\MessageBuilder\BuilderCollection;
use Sokil\NotificationBundle\Schema\ConfigurationProvider;
use Sokil\TaskStockBundle\Notification\Message\TaskChangeMessageBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sokil\TaskStockBundle\Event\TaskChangeEvent;
use Sokil\NotificationBundle\TransportProvider;

class TaskNotificationListener implements EventSubscriberInterface
{
    /**
     * @var ConfigurationProvider
     */
    private $schemaConfigurationProvider;

    /**
     * @var TransportProvider
     */
    private $transportProvider;

    /**
     * @var BuilderCollection
     */
    private $messageBuilderCollection;

    public function __construct(
        ConfigurationProvider $schemaProvider,
        TransportProvider $transportProvider,
        BuilderCollection $messageBuilderCollection
    ) {
        $this->schemaConfigurationProvider = $schemaProvider;
        $this->transportProvider = $transportProvider;
        $this->messageBuilderCollection = $messageBuilderCollection;
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

                // build message
                /* @var TaskChangeMessageBuilder $messageBuilder */
                $messageBuilder = $this->messageBuilderCollection->getBuilder('taskChange', $transportName);
                $message = $messageBuilder
                    ->setTask($task)
                    ->setUser($user)
                    ->setChanges($changes)
                    ->createMessage();

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
