<?php

namespace Sokil\TaskStockBundle\Notification\Message;

use Sokil\Diff\Change;
use Sokil\NotificationBundle\MessageBuilder\AbstractBuilder;
use Sokil\NotificationBundle\MessageBuilder\FixtureBuilder;
use Sokil\State\State;
use Sokil\TaskStockBundle\Entity\TaskCategory;
use Sokil\TaskStockBundle\Entity\TaskCategoryLocalization;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\TaskStockBundle\Entity\TaskProject;
use Sokil\UserBundle\Entity\User;

/**
 * @property TaskChangeMessageBuilder $messageBuilder
 */
class FixtureTaskChangeMessageBuilder extends FixtureBuilder
{
    public function __construct(AbstractBuilder $messageBuilder)
    {
        if (!($messageBuilder instanceof TaskChangeMessageBuilder)) {
            throw new \InvalidArgumentException('Message builder must be instance of TaskChangeMessageBuilder');
        }

        parent::__construct($messageBuilder);
    }

    public function createFixture() {

        $lang = 'en';

        // user
        $user = new User();
        $user->setName('User name');

        $this->setPrivateProperty($user, 'id', 42);

        // task
        $task = new Task();
        $task
            ->setName('New task name')
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

        // project
        $project = new TaskProject();
        $project->setStateSchemaId(0);
        $task->setProject($project);

        // changes
        $changes = [
            'project' => new Change('Old project', 'New project'),
            'name' => new Change('Old task name', 'New task name'),
            'date' => new Change(new \DateTime('2014-10-14 00:00:00'), new \DateTime('2014-10-14 00:00:00')),
            'owner' => new Change(null, $user),
            'assignee' => new Change(null, $user),
            'category' => new Change($oldCategory, $newCategory),
            'description' => new Change('Old task description', 'New task description'),
            'stateName' => new Change(
                'new',
                'in_progress'
            ),
        ];

        return $this->messageBuilder
            ->setTask($task)
            ->setUser($user)
            ->setChanges($changes)
            ->createMessage();
    }

    private function setPrivateProperty($object, $propertyName, $propertyValue)
    {
        $reflectionClass = new \ReflectionClass($object);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $propertyValue);
    }
}