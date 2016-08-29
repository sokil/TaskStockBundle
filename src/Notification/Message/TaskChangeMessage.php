<?php

namespace Sokil\TaskStockBundle\Notification\Message;

use Sokil\Diff\Change;
use Sokil\Diff\Renderer;
use Sokil\NotificationBundle\Message\EmailMessageInterface;
use Sokil\TaskStockBundle\Common\Localization\LocalizedInterface;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\TaskStockBundle\State\TaskStateHandler;
use Sokil\UserBundle\Entity\User;
use Sokil\State\State;
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

    public function getTranslator()
    {
        return $this->translator;
    }

    public function getSubject()
    {
        return '[' . $this->task->getCode() . '] ' . $this->task->getName();
    }

    public function getBody()
    {
        $lang = $this->translator->getLocale();

        return $this->templateEngine->render('TaskStockBundle:EmailMessageProvider:task.change.html.twig', [
            'task' => $this->task,
            'user' => $this->user,
            'changes' => array_map(function(Change $change) use ($lang) {
                $oldValue = $change->getOldValue();
                $newValue = $change->getNewValue();

                $changeSerialized = [
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                ];

                foreach ($changeSerialized as $key => $value) {
                    if ($value instanceof \DateTime) {
                        $changeSerialized[$key] = $value->format('d.m.Y H:i:s');
                    } elseif ($key === 'stateName') {
//                        $state = null;
//                        $changeSerialized[$key] = $this
//                            ->translator
//                            ->trans($state->getMetadata('label'));
                    } elseif ($value instanceof LocalizedInterface) {
                        $changeSerialized[$key] = $value->getLocalization($lang);
                    }
                }

                if ($this->textDiffRenderer) {
                    $changeSerialized['diff'] =  $this->textDiffRenderer->render(
                        $changeSerialized['oldValue'],
                        $changeSerialized['newValue']
                    );
                }

                return $changeSerialized;
            }, $this->changes),
        ]);
    }
}