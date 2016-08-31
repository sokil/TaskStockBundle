<?php

namespace Sokil\TaskStockBundle\Notification\Message;

use Sokil\Diff\Change;
use Sokil\Diff\Renderer;
use Sokil\NotificationBundle\Message\MessageInterface;
use Sokil\NotificationBundle\MessageBuilder\AbstractBuilder;
use Sokil\State\State;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\TaskStockBundle\State\TaskStateHandlerBuilder;
use Sokil\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TaskChangeMessageBuilder extends AbstractBuilder
{
    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @var TranslatorInterface
     */
    private $translator;


    /**
     * @var Renderer
     */
    protected $textDiffRenderer;

    /**
     * @var TaskStateHandlerBuilder
     */
    protected $taskStateHandlerBuilder;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Task
     */
    private $task;

    /**
     * @var Change[]
     */
    private $changes;


    /**
     * @param EngineInterface $engine
     * @param TranslatorInterface $translator
     * @param Renderer $renderer
     */
    public function __construct(
        EngineInterface $engine,
        TranslatorInterface $translator,
        Renderer $renderer,
        TaskStateHandlerBuilder $taskStateHandlerBuilder
    ) {
        $this->templateEngine = $engine;
        $this->translator = $translator;
        $this->textDiffRenderer = $renderer;
        $this->taskStateHandlerBuilder = $taskStateHandlerBuilder;
    }

    /**
     * @param User $user
     * @return TaskChangeMessageBuilder
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param Task $task
     * @return TaskChangeMessageBuilder
     */
    public function setTask(Task $task)
    {
        $this->task = $task;
        return $this;
    }

    /**
     * @param Change[] $changes
     * @return TaskChangeMessageBuilder
     */
    public function setChanges(array $changes)
    {
        $this->changes = array_filter($changes, function($change) {
            return $change instanceof Change;
        });

        return $this;
    }

    /**
     * @return TaskChangeMessage
     */
    public function createMessage()
    {
        $message = new TaskChangeMessage(
            $this->templateEngine,
            $this->translator,
            $this->textDiffRenderer,
            $this->taskStateHandlerBuilder->build($this->task),
            $this->user,
            $this->task,
            $this->changes
        );

        return $message;
    }
}