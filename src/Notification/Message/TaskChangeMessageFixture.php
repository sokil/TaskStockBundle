<?php

namespace Sokil\TaskStockBundle\Notification\Message;

use Sokil\Diff\Change;
use Sokil\NotificationBundle\Message\MessageFixtureInterface;
use Sokil\State\State;
use Sokil\TaskStockBundle\Entity\TaskCategory;
use Sokil\TaskStockBundle\Entity\TaskCategoryLocalization;
use Sokil\NotificationBundle\Message\MessageInterface;

use Sokil\TaskStockBundle\Entity\Task;
use Sokil\UserBundle\Entity\User;

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
            'project' => new Change('Old project', 'New project'),
            'name' => new Change('Old task name', 'New task name'),
            'amount' => new Change(10, 42.2),
            'date' => new Change(new \DateTime('2014-10-14 00:00:00'), new \DateTime('2014-10-14 00:00:00')),
            'owner' => new Change(null, $user),
            'assignee' => new Change(null, $user),
            'category' => new Change($oldCategory, $newCategory),
            'location' => new Change('Old location', 'New location'),
            'description' => new Change('Old task description', 'New task description'),
            'stateName' => new Change(
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