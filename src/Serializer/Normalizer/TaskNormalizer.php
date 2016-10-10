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

    private $availableModificators = [
        'permissions',
        'owner',
        'assignee',
        'project',
        'category',
        'parentTask',
        'state',
        'subTasks',
        'categoryList',
    ];

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

        // get normalization groups
        if (empty($context['groups'])) {
            $context['groups'] = $this->availableModificators;
        } else {
            $context['groups'] = array_intersect(
                $context['groups'],
                $this->availableModificators
            );
        }

        // add normalized data
        $taskArray = [
            'id'            => $task->getId(),
            'name'          => $task->getName(),
            'description'   => $task->getDescription(),
            'date'          => $task->getDate('d.m.Y H:i:s'),
        ];

        foreach ($context['groups'] as $group) {
            $taskArray[$group] = call_user_func(
                [$this, 'get' . $group],
                $task
            );
        }

        return array_filter($taskArray);
    }

    private function getOwner(Task $task)
    {
        // get task owner
        $taskOwner = $task->getOwner();
        if (!$taskOwner) {
            return;
        }

        return [
            'id' => $taskOwner->getId(),
            'name' => $taskOwner->getName(),
            'gravatar' => $taskOwner->getGravatarDefaultUrl(),
        ];
    }

    private function getAssignee(Task $task)
    {
        $taskAssignee = $task->getAssignee();
        if (!$taskAssignee) {
            return;
        }

        return [
            'id' => $taskAssignee->getId(),
            'name' => $taskAssignee->getName(),
            'gravatar' => $taskAssignee->getGravatarDefaultUrl(),
        ];
    }

    private function getProject(Task $task)
    {
        $taskProject = $task->getProject();
        if (!$taskProject) {
            return;
        }

        return [
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

    private function getParentTask(Task $task)
    {
        $parentTask = $task->getParent();
        if (!$parentTask) {
            return;
        }

        return [
            'id' => $parentTask->getId(),
            'name' => $parentTask->getName(),
        ];
    }

    private function getState(Task $task)
    {
        /* @var $stateHandler TaskStateHandler */
        if (!$task->hasStates()) {
            return;
        }

        $stateHandler = $this->taskStateHandlerBuilder->build($task);
        $taskState = $stateHandler->getCurrentState();

        return [
            'currentState' => [
                'name'  => $taskState->getName(),
                'label' => $this->translator->trans($taskState->getMetadata('label')),
            ],
            'nextStates' => array_map(
                function(\Sokil\State\Transition $transition) {
                    return [
                        'label' => $this->translator->trans($transition->getMetadata('label')),
                        'state' => $transition->getResultingStateName(),
                        'icon' => $transition->getMetadata('icon'),
                    ];
                },
                $stateHandler->getNextStateTransitions()
            ),
        ];

    }

    private function getCategory(Task $task)
    {
        $taskCategory = $task->getCategory();
        if (!$taskCategory) {
            return;
        }

        return [
            'id' => $taskCategory->getId(),
            'name' => $taskCategory->getLocalization($this->locale)->getName(),
        ];
    }

    private function getPermissions(Task $task)
    {
        return [
            'edit' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_EDIT, $task),
            'changeProject' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_PROJECT, $task),
            'changeAssignee' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_ASSIGNEE, $task),
            'changeOwner' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_OWNER, $task),
            'viewAttachments' => true,
        ];
    }

    private function getCategoryList(Task $task)
    {
        $project = $task->getProject();
        if (!$project) {
            return null;
        }

        $categorySchemaId = $project->getTaskCategorySchemaId();
        if (!$categorySchemaId) {
            return null;
        }

        $categorySchema = $this
            ->taskCategorySchemaRepository
            ->find($categorySchemaId);

        if (!$categorySchema) {
            return null;
        }

        return array_map(
            function(TaskCategory $category) {
                return [
                    'id' => $category->getId(),
                    'name' => $category->getLocalization($this->locale)->getName(),
                ];
            },
            $categorySchema->getCategories()->toArray()
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