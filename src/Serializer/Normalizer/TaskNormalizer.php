<?php

namespace Sokil\TaskStockBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityRepository;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\TaskStockBundle\Entity\TaskCategory;
use Sokil\TaskStockBundle\State\TaskStateHandler;
use Sokil\TaskStockBundle\State\TaskStateHandlerBuilder;
use Sokil\TaskStockBundle\Voter\TaskVoter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Translation\TranslatorInterface;

class TaskNormalizer implements NormalizerInterface
{
    const NORMALIZER_GROUP_DEFAULTS = 'defaults';
    const NORMALIZER_GROUP_EDIT = 'edit';
    const NORMALIZER_GROUP_VIEW = 'view';

    /**
     * @var EntityRepository
     */
    private $taskCategorySchemaRepository;

    /**
     * @var TaskStateHandlerBuilder
     */
    private $taskStateHandlerBuilder;

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $locale;

    /**
     * TaskNormalizer constructor.
     * @param TaskStateHandlerBuilder $taskStateHandlerBuilder
     * @param AuthorizationChecker $authorizationChecker
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityRepository $taskProjectRepository,
        TaskStateHandlerBuilder $taskStateHandlerBuilder,
        AuthorizationChecker $authorizationChecker,
        TranslatorInterface $translator,
        $locale
    ) {
        $this->taskCategorySchemaRepository = $taskProjectRepository;
        $this->taskStateHandlerBuilder = $taskStateHandlerBuilder;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function normalize(
        $task,
        $format = null,
        array $context = array()
    ) {
        if (!($task instanceof Task)) {
            throw new \InvalidArgumentException('Normalized object must be instance of ' . Task::class);
        }

        if (empty($context['groups'])) {
            $context['groups'] = [];
        }

        $taskArray = [];

        $taskArray['permissions'] = [
            'edit' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_EDIT, $task),
            'changeProject' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_PROJECT, $task),
            'changeAssignee' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_ASSIGNEE, $task),
            'changeOwner' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_OWNER, $task),
            'viewAttachments' => true,
        ];

        if (in_array(self::NORMALIZER_GROUP_DEFAULTS, $context['groups'])) {
            return $taskArray;
        }
        
        $taskArray += [
            'id'            => $task->getId(),
            'name'          => $task->getName(),
            'description'   => $task->getDescription(),
            'date'          => $task->getDate('d.m.Y H:i:s'),
        ];

        // get task owner
        $taskOwner = $task->getOwner();
        if ($taskOwner) {
            $taskArray['owner'] = [
                'id' => $taskOwner->getId(),
                'name' => $taskOwner->getName(),
                'gravatar' => $taskOwner->getGravatarDefaultUrl(),
            ];
        }

        // get task assignee
        $taskAssignee = $task->getAssignee();
        if ($taskAssignee) {
            $taskArray['assignee'] = [
                'id' => $taskAssignee->getId(),
                'name' => $taskAssignee->getName(),
                'gravatar' => $taskAssignee->getGravatarDefaultUrl(),
            ];
        }

        // task project
        $taskProject = $task->getProject();
        if ($taskProject) {
            $taskArray['project'] = [
                'id' => $taskProject->getId(),
                'code' => $taskProject->getCode(),
                'name' => $taskProject->getName(),
                'categorySchema' => [
                    'id' => $taskProject->getTaskCategorySchemaId(),
                ],
                'notificationSchema' => [
                    'id' => $taskProject->getNotificationSchemaId(),
                ],
            ];
        }

        // category
        $taskCategory = $task->getCategory();
        if ($taskCategory) {
            $taskArray['category'] = [
                'id' => $taskCategory->getId(),
                'name' => $taskCategory->getLocalization($this->locale)->getName(),
            ];
        }

        // add parent task
        $parentTask = $task->getParent();
        if ($parentTask) {
            $taskArray['parent'] = [
                'id' => $parentTask->getId(),
                'name' => $parentTask->getName(),
            ];
        }

        // get state handler
        /* @var $stateHandler TaskStateHandler */
        if ($task->hasStates()) {
            $stateHandler = $this->taskStateHandlerBuilder->build($task);
            $taskState = $stateHandler->getCurrentState();

            $taskArray['state'] = [
                'name'  => $taskState->getName(),
                'label' => $this->translator->trans($taskState->getMetadata('label')),
            ];

            $taskArray['nextStates'] = array_map(function(\Sokil\State\Transition $transition) {
                return [
                    'label' => $this->translator->trans($transition->getMetadata('label')),
                    'state' => $transition->getResultingStateName(),
                    'icon' => $transition->getMetadata('icon'),
                ];
            }, $stateHandler->getNextStateTransitions());
        }

        // dictionaries for editing
        if (in_array(self::NORMALIZER_GROUP_EDIT, $context['groups'])) {
            if (true === $taskArray['permissions']['edit']) {
                $taskArray['edit'] = [];
                // list of available categories
                $taskArray['edit']['categories'] = $this->getAvailableTaskCategories($task);
            }
        }

        if (in_array(self::NORMALIZER_GROUP_VIEW, $context['groups'])) {
            $taskArray['subtasks'] = $this->getSubTasks($task);
        }

        return $taskArray;
    }

    private function getAvailableTaskCategories(Task $task)
    {
        return array_map(
            function(TaskCategory $category) {
                return [
                    'id' => $category->getId(),
                    'name' => $category->getLocalization($this->locale)->getName(),
                ];
            },
            $this
                ->taskCategorySchemaRepository
                ->find($task->getProject()->getTaskCategorySchemaId())
                ->getCategories()
                ->toArray()
        );
    }

    private function getSubTasks(Task $task)
    {
        return $task
            ->getSubTasks()
            ->map(
                function(Task $subTask) {
                    $subTaskData = [
                        'id' => $subTask->getId(),
                        'name' => $subTask->getName(),
                    ];

                    // sub task state
                    /* @var $stateHandler TaskStateHandler */
                    if ($subTask->hasStates()) {
                        $stateHandler = $this->taskStateHandlerBuilder->build($subTask);
                        $subTaskState = $stateHandler->getCurrentState();
                        $subTaskData['state'] = [
                            'name'  => $subTaskState->getName(),
                            'label' => $this->translator->trans($subTaskState->getMetadata('label')),
                        ];
                    }

                    // assignee
                    $assignee = $subTask->getAssignee();
                    if ($assignee) {
                        $subTaskData['assignee'] =[
                            'id'        => $assignee->getId(),
                            'name'      => $assignee->getName(),
                            'gravatar'  => $assignee->getGravatarDefaultUrl(),
                        ];
                    }

                    return $subTaskData;
                }
            )
            ->toArray();
    }

    public function supportsNormalization($data, $format = null)
    {
        return true;
    }
}