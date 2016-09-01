<?php

namespace Sokil\TaskStockBundle\Notification\Message;

use Sokil\Diff\Change;
use Sokil\Diff\Renderer;
use Sokil\NotificationBundle\Message\EmailMessageInterface;
use Sokil\TaskStockBundle\Common\Localization\LocalizedInterface;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\TaskStockBundle\State\TaskStateHandler;
use Sokil\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TaskChangeMessage implements EmailMessageInterface
{
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
     * @var TaskStateHandler
     */
    protected $taskStateHandler;


    /**
     * @param EngineInterface $engine
     * @param TranslatorInterface $translator
     * @param Renderer $renderer
     * @param TaskStateHandler $taskStateHandler
     * @param User $user
     * @param Task $task
     * @param Change[] $changes array of changes
     */
    public function __construct(
        EngineInterface $engine,
        TranslatorInterface $translator,
        Renderer $renderer,
        TaskStateHandler $taskStateHandler,
        User $user,
        Task $task,
        array $changes
    ) {
        $this->templateEngine = $engine;
        $this->translator = $translator;
        $this->textDiffRenderer = $renderer;
        $this->taskStateHandler = $taskStateHandler;
        $this->user = $user;
        $this->task = $task;
        $this->changes = $changes;
    }

    public function getSubject()
    {
        return '[' . $this->task->getCode() . '] ' . $this->task->getName();
    }

    private function getSerializedChanges($lang)
    {
        $serializedChanges = [];

        foreach ($this->changes as $fieldName => $change) {
            $oldValue = $change->getOldValue();
            $newValue = $change->getNewValue();

            $serializedChange = [
                'oldValue' => $oldValue,
                'newValue' => $newValue,
            ];

            foreach ($serializedChange as $key => $value) {
                if ($value instanceof \DateTime) {
                    $serializedChange[$key] = $value->format('d.m.Y H:i:s');
                } else if ($fieldName === 'stateName') {
                    $state = $this->taskStateHandler->getState($value);
                    $serializedChange[$key] = $this
                        ->translator
                        ->trans($state->getMetadata('label'));
                } else if ($value instanceof User) {
                    $serializedChange[$key] = (string)$value;
                } else if ($value instanceof LocalizedInterface) {
                    $serializedChange[$key] = $value->getLocalization($lang);
                }
            }

            if ($this->textDiffRenderer) {
                $serializedChange['diff'] =  $this->textDiffRenderer->render(new Change(
                    $serializedChange['oldValue'],
                    $serializedChange['newValue']
                ));
            }

            $serializedChanges[$fieldName] = $serializedChange;
        }

        return $serializedChanges;
    }

    public function getBody()
    {
        return $this->templateEngine->render('TaskStockBundle:EmailMessageProvider:task.change.html.twig', [
            'task' => $this->task,
            'user' => $this->user,
            'changes' => $this->getSerializedChanges($this->translator->getLocale()),
        ]);
    }
}