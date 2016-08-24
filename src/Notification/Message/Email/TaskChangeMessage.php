<?php

namespace Sokil\TaskStockBundle\Notification\Message\Email;

use Sokil\Diff\Renderer;
use Sokil\NotificationBundle\Message\DiffRendererAwareInterface;
use Sokil\NotificationBundle\Message\EmailMessageInterface;
use Sokil\NotificationBundle\Message\TemplateAwareInterface;
use Sokil\NotificationBundle\Message\TranslatorAwareInterface;
use Sokil\TaskStockBundle\Common\Localization\LocalizedInterface;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\UserBundle\Entity\User;
use Sokil\TaskStockBundle\Common\Dto\ChangedValue;
use Sokil\State\State;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TaskChangeMessage implements
    EmailMessageInterface,
    DiffRendererAwareInterface,
    TemplateAwareInterface,
    TranslatorAwareInterface
{
    private $user;

    private $task;

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

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function setTask(Task $task)
    {
        $this->task = $task;
        return $this;
    }

    /**
     * @param array $changes array of \Sokil\TaskStockBundle\Dto\ChangedValue instances
     * @return $this
     */
    public function setChanges(array $changes)
    {
        $this->changes = $changes;
        return $this;
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
            'changes' => array_map(function(ChangedValue $change) use ($lang) {
                $oldValue = $change->getOldValue();
                $newValue = $change->getNewValue();

                $changeSerialized = [
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                ];

                foreach ($changeSerialized as $key => $value) {
                    if ($value instanceof \DateTime) {
                        $changeSerialized[$key] = $value->format('d.m.Y H:i:s');
                    } elseif ($value instanceof State) {
                        $changeSerialized[$key] = $this
                            ->translator
                            ->trans($value->getMetadata('label'));
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

    /**
     * @param EngineInterface $engine
     * @return $this
     */
    public function setTemplateEngine(EngineInterface $engine)
    {
        $this->templateEngine = $engine;
        return $this;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        return $this;
    }

    public function getTranslator()
    {
        return $this->translator;
    }

    public function setDiffRenderer(Renderer $renderer)
    {
        $this->textDiffRenderer = $renderer;
        return $this;
    }
}