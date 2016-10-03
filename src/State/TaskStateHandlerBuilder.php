<?php

namespace Sokil\TaskStockBundle\State;

use Sokil\State\Configuration\ArrayConfiguration;
use Sokil\State\MachineBuilder;
use Sokil\TaskStockBundle\Entity\Task;

class TaskStateHandlerBuilder
{
    /**
     * @var array
     */
    private $stateConfiguration;

    public function __construct(array $configuration)
    {
        $this->stateConfiguration = $configuration;
    }

    private function getStateConfiguration($stateSchemaId)
    {
        foreach ($this->stateConfiguration as $stateConfiguration) {
            if ($stateConfiguration['id'] === $stateSchemaId) {
                return $stateConfiguration;
            }
        }

        throw new \Exception('Unknown task state schema');
    }

    /**
     * Get state configuration for passed task
     *
     * @param Task $task
     * @return array|null
     */
    protected function getTaskStateConfiguration(Task $task)
    {
        // get task state schema id
        $stateSchemaId = $task->getProject()->getStateSchemaId();
        if (empty($stateSchemaId)) {
            throw new \Exception('Task has no related state configuration');
        }

        return $this->getStateConfiguration($stateSchemaId);
    }

    /**
     * @return TaskStateHandler
     */
    public function build(Task $task)
    {
        $stateConfiguration = $this->getTaskStateConfiguration($task);

        // init builder
        $builder = new MachineBuilder();
        $builder->configure(new ArrayConfiguration($stateConfiguration['states']));

        // build state machine
        $stateMachine = $builder->getMachine();
        if (empty($task->getStateName())) {
            $stateMachine->initialize();
        } else {
            $stateMachine->initialize($task->getStateName());
        }

        return new TaskStateHandler($task, $stateMachine);
    }
}