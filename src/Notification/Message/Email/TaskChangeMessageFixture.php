<?php

namespace Sokil\TaskStockBundle\Notification\Message\Email;

use Sokil\NotificationBundle\Message\MessageFixtureInterface;
use Sokil\State\State;
use Sokil\TaskStockBundle\Entity\TaskCategory;
use Sokil\TaskStockBundle\Entity\TaskCategoryLocalization;
use Sokil\NotificationBundle\Message\MessageInterface;

use Sokil\TaskStockBundle\Entity\Task;
use Sokil\UserBundle\Entity\User;

use Sokil\NotificationBundle\Message\ChangedValue;

class TaskChangeMessageFixture implements MessageFixtureInterface
{
    public function apply(MessageInterface $message)
    {
        if (!($message instanceof TaskChangeMessage)) {
            throw new \InvalidArgumentException('Message must be instance of TaskChangeMessage');
        }

        $lang = $message->getTranslator()->getLocale();

        // user
        $user = new User();
        $user->setName('User name');

        $this->setPrivateProperty($user, 'id', 42);

        // task
        $task = new Task();
        $task
            ->setName('New task name')
            ->setAmount(42.2)
            ->setAssignee($user)
            ->setDate(new \DateTime('2014-10-14 00:00:00'))
            ->setDescription('New task description');

        // id
        $this->setPrivateProperty($task, 'id', 42);

        // category
        $oldCategory = new TaskCategory();
        $this->setPrivateProperty($oldCategory, 'id', 77);
        $oldCategoryLocalization = new TaskCategoryLocalization();
        $oldCategoryLocalization
            ->setLang($lang)
            ->setName('old_category_name')
            ->setDescription('old_category_description')
            ->setTaskCategory($oldCategory);
        $oldCategory->addLocalization($oldCategoryLocalization);

        $newCategory = new TaskCategory();
        $this->setPrivateProperty($newCategory, 'id', 88);
        $newCategoryLocalization = new TaskCategoryLocalization();
        $newCategoryLocalization
            ->setLang($lang)
            ->setName('new_category_name')
            ->setDescription('new_category_description')
            ->setTaskCategory($newCategory);
        $newCategory->addLocalization($newCategoryLocalization);

        // changes
        $changes = [
            'project' => new ChangedValue('Old project', 'New project'),
            'name' => new ChangedValue('Old task name', 'New task name'),
            'amount' => new ChangedValue(10, 42.2),
            'date' => new ChangedValue(new \DateTime('2014-10-14 00:00:00'), new \DateTime('2014-10-14 00:00:00')),
            'owner' => new ChangedValue(null, $user),
            'assignee' => new ChangedValue(null, $user),
            'category' => new ChangedValue($oldCategory, $newCategory),
            'location' => new ChangedValue('Old location', 'New location'),
            'description' => new ChangedValue('Old task description', 'New task description'),
            'stateName' => new ChangedValue(
                new State('in_progress', ['label' => 'task_status_in_progress']),
                new State('resolved', ['label' => 'task_status_resolved'])
            ),
        ];

        $message
            ->setTask($task)
            ->setUser($user)
            ->setChanges($changes);
    }

    private function setPrivateProperty($object, $propertyName, $propertyValue)
    {
        $reflectionClass = new \ReflectionClass($object);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $propertyValue);
    }
}