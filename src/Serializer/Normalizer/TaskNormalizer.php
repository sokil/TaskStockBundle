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

        $permissions = [
            'edit' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_EDIT, $task),
            'changeProject' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_PROJECT, $task),
            'changeAssignee' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_ASSIGNEE, $task),
            'changeOwner' => $this->authorizationChecker->isGranted(TaskVoter::PERMISSION_CHANGE_OWNER, $task),
            'viewAttachments' => true,
        ];
        
        // get category of task
        $taskCategory = $task->getCategory();

        // get task owner
        $taskOwner = $task->getOwner();

        // get task assignee
        $taskAssignee = $task->getAssignee();

        // task project
        $taskProject = $task->getProject();

        $taskArray = [
            'id'            => $task->getId(),
            'name'          => $task->getName(),
            'description'   => $task->getDescription(),
            'date'          => $task->getDate('d.m.Y H:i:s'),
            'owner' => [
                'id' => $taskOwner->getId(),
                'name' => $taskOwner->getName(),
                'gravatar' => $taskOwner->getGravatarDefaultUrl(),
            ],
            'assignee' => $taskAssignee ? [
                'id' => $taskAssignee->getId(),
                'name' => $taskAssignee->getName(),
                'gravatar' => $taskAssignee->getGravatarDefaultUrl(),
            ] : null,
            'project' => $taskProject ? [
                'id' => $taskProject->getId(),
                'code' => $taskProject->getCode(),
                'name' => $taskProject->getName(),
            ] : null,
            'permissions' => $permissions,
        ];

        // category
        if ($taskCategory) {
            $taskArray['category'] = [
                'id' => $taskCategory->getId(),
                'name' => $taskCategory->getLocalization($this->locale)->getName(),
            ];
        }

        //  allowed categories
        if (!empty($context['groups']) && in_array('edit', $context['groups'])) {

            $categoryList = $this
                ->taskCategorySchemaRepository
                ->find($task->getProject()->getTaskCategorySchemaId())
                ->getCategories()
                ->toArray();

            if ($categoryList) {
                $taskArray['category']['list'] = array_map(
                    function(TaskCategory $category) {
                        return [
                            'id' => $category->getId(),
                            'name' => $category->getLocalization($this->locale)->getName(),
                        ];
                    },
                    $categoryList
                );
            }
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

        // add sub tasks
        if (!empty($context['groups']) && in_array('subdtasks', $context['groups'])) {
            $taskArray['subtasks'] = $task->getSubTasks()->map(function(Task $subTask) {
                $subtaskData = [
                    'id' => $subTask->getId(),
                    'name' => $subTask->getName(),
                ];

                // sub task state
                /* @var $stateHandler TaskStateHandler */
                if ($subTask->hasStates()) {
                    $stateHandler = $this->taskStateHandlerBuilder->build($subTask);
                    $subTaskState = $stateHandler->getCurrentState();
                    $subtaskData['state'] = [
                        'name'  => $subTaskState->getName(),
                        'label' => $this->translator->trans($subTaskState->getMetadata('label')),
                    ];
                }

                // assignee
                $assignee = $subTask->getAssignee();
                if ($assignee) {
                    $subtaskData['assignee'] =[
                        'id'        => $assignee->getId(),
                        'name'      => $assignee->getName(),
                        'gravatar'  => $assignee->getGravatarDefaultUrl(),
                    ];
                }

                return $subtaskData;
            })->toArray();
        }

        // add parent task
        $parentTask = $task->getParent();
        if ($parentTask) {
            $taskArray['parent'] = [
                'id' => $parentTask->getId(),
                'name' => $parentTask->getName(),
            ];
        }

        return $taskArray;
    }

    public function supportsNormalization($data, $format = null)
    {
        return true;
    }
}