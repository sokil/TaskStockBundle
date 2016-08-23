<?php

namespace Sokil\TaskStockBundle\Event;

use Sokil\TaskStockBundle\Entity\Task;
use Sokil\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;
use Doctrine\Common\PropertyChangedListener;

use Sokil\NotificationBundle\Message\ChangedValue;

class TaskChangeEvent extends Event implements PropertyChangedListener
{
    private $tasks = [];

    /**
     * @var array of changed attributes
     */
    private $changes = [];

    /**
     * @var User who change the task
     */
    private $user;

    /**
     * @return array of changes fields
     */
    public function getChanges()
    {
        return $this->changes;
    }

    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function propertyChanged($sender, $propertyName, $oldValue, $newValue)
    {
        if (!($sender instanceof Task)) {
            throw new \Exception('Wrong sender specified');
        }

        $this->tasks[$sender->getId()] = $sender;
        $this->changes[$sender->getId()][$propertyName] = new ChangedValue($oldValue, $newValue);
    }
}