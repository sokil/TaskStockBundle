<?php

namespace Sokil\TaskStockBundle\State;

use Sokil\State\Machine;
use Sokil\TaskStockBundle\Entity\Task;

class TaskStateHandler
{
    private $task;

    private $stateMachine;

    public function __construct(Task $task, Machine $stateMachine)
    {
        $this->task = $task;
        $this->stateMachine = $stateMachine;
    }

    public function getState()
    {
        return $this->stateMachine->getCurrentState();
    }

    /**
     * Get next state transitions
     * @return array
     * @throws \Exception
     */
    public function getNextStateTransitions()
    {
        return $this->stateMachine->getNextTransitions();
    }

    /**
     * @param $transitionName
     * @return $this
     */
    public function processStateTransition($transitionName)
    {
        // remember old stare
        $oldState = $this->stateMachine->getCurrentState();

        // set new state
        $this->stateMachine->process($transitionName);

        // remember new state
        $newState = $this->stateMachine->getCurrentState();

        // set new state name to entity
        $this->task->setStateName($newState->getName());

        return $this;
    }
}