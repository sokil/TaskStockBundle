<?php

namespace Sokil\TaskStockBundle\Controller;

use Sokil\TaskStockBundle\Entity\Task;
use Sokil\TaskStockBundle\Event\TaskChangeEvent;
use Sokil\TaskStockBundle\State\TaskStateHandler;
use Sokil\TaskStockBundle\Voter\TaskVoter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TaskController extends Controller
{
    /**
     * @Route("/tasks", name="tasks")
     * @Method("GET")
     */
    public function tasksAction(Request $request)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }
        
        // create repository
        /* @var $repository \Doctrine\ORM\EntityRepository */
        $repository = $this->getDoctrine()->getRepository('TaskStockBundle:Task');
        $queryBuilder = $repository->createQueryBuilder('t');
        $queryBuilder
            ->where($queryBuilder->expr()->eq('t.deleted', 0))
            ->andWhere($queryBuilder->expr()->isNull('t.parent'))
            ->orderBy('t.date', 'DESC');

        // if client - show only own tasks
        if ($this->isGranted('ROLE_CLIENT')) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('t.owner', $this->getUser()->getId()));
        }

        // pager
        $limit = (int) $request->get('limit', 20);
        if($limit > 100) {
            $limit = 100;
        }
        $queryBuilder->setMaxResults($limit);

        $offset = (int) $request->get('offset', 0);
        if($offset > 1000) {
            $offset = 1000;
        }
        $queryBuilder->setFirstResult($offset);
        
        // categories
        $category = $request->get('category');
        if (is_array($category)) {
            $queryBuilder->andWhere($queryBuilder
                ->expr()
                ->in(
                    't.category',
                    array_filter(array_map('intval', $category))
                )
            );
        }

        // get list of tasks
        $tasks = $queryBuilder->getQuery()->getResult();

        // get total count of tasks
        $paginator = new Paginator($queryBuilder);

        // get current locale
        $locale = $request->getLocale();
        $currency = $this->container->getParameter('task_stock.currency');
        
        // return response
        return new JsonResponse([
            'tasks' => array_map(function(Task $task) use($locale, $currency) {
                // category
                $category = $task->getCategory();

                /* @var $task \Sokil\TaskStockBundle\Entity\Task */
                return [
                    'id' => $task->getId(),
                    'name' => $task->getName(),
                    'description' => strip_tags($task->getAnnotation()),
                    'location' => $task->getLocation(),
                    'date' => $task->getDate('d.m.Y H:i'),
                    'amountFormatted' => $task->getFormattedAmount($locale, $currency),
                    'amount' => $task->getAmount(),
                    'category' => $category ? [
                        'id' => $category->getId(),
                        'name' => $category->getLocalization($locale)->getName(),
                    ] : null,
                    'permissions' => [
                        TaskVoter::PERMISSION_EDIT => $this->isGranted('edit', $task),
                    ],
                ];
            }, $tasks),
            'tasksCount' => $paginator->count(),
        ]);
    }

    /**
     * @Route("/tasks/{id}", name="get_task", requirements={"id": "\d+"})
     * @Route("/tasks/new", name="get_default_task")
     * @Method({"GET"})
     */
    public function getTaskAction(Request $request, $id = null)
    {
        /* @var $task \Sokil\TaskStockBundle\Entity\Task */

        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        // get task
        if ($id) {
            $task = $this->getDoctrine()
                ->getRepository('TaskStockBundle:Task')
                ->find($id);

            if (!$task || $task->isDeleted()) {
                throw new NotFoundHttpException('Task not found');
            }
        } else {
            $task = new Task();
            $task->setOwner($this->getUser());
        }

        // authorize
        if (!$this->isGranted(TaskVoter::PERMISSION_VIEW, $task)) {
            throw $this->createAccessDeniedException();
        }

        // get category of task
        $taskCategory = $task->getCategory();

        // get task owner
        $taskOwner = $task->getOwner();

        // get task assignee
        $taskAssignee = $task->getAssignee();

        // task project
        $taskProject = $task->getProject();

        // editable
        $permissions = [
            'edit' => $this->isGranted(TaskVoter::PERMISSION_EDIT, $task),
            'changeProject' => $this->isGranted(TaskVoter::PERMISSION_CHANGE_PROJECT, $task),
            'changeAssignee' => $this->isGranted(TaskVoter::PERMISSION_CHANGE_ASSIGNEE, $task),
            'changeOwner' => $this->isGranted(TaskVoter::PERMISSION_CHANGE_OWNER, $task),
            'viewAttachments' => true,
        ];

        // return json response
        $translator = $this->get('translator');

        if (!$id) {
            return new JsonResponse([
                'permissions' => $permissions,
            ]);
        }

        // get state handler
        /* @var $stateHandler TaskStateHandler */
        $stateHandler = $this->get('task_stock.task_state_handler_builder')->build($task);
        $taskState = $stateHandler->getCurrentState();

        // build response
        $response = [
            'id'            => $task->getId(),
            'name'          => $task->getName(),
            'description'   => $task->getDescription(),
            'date'          => $task->getDate('d.m.Y H:i:s'),
            'location'      => $task->getLocation(),
            'amount'        => $task->getAmount(),
            'state'         => [
                'name'  => $taskState->getName(),
                'label' => $translator->trans($taskState->getMetadata('label')),
            ],
            'nextStates'    => array_map(function(\Sokil\State\Transition $transition) use (
                $translator
            ) {
                return [
                    'label' => $translator->trans($transition->getMetadata('label')),
                    'state' => $transition->getResultingStateName(),
                    'icon' => $transition->getMetadata('icon'),
                ];
            }, $stateHandler->getNextStateTransitions()),
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
            'category' => $taskCategory ? [
                'id' => $taskCategory->getId(),
                'name' => $taskCategory->getLocalization($request->getLocale())->getName(),
            ] : null,
            'permissions' => $permissions,
        ];

        // add subtasks
        if ($request->get('subtasks')) {
            $response['subtasks'] = $task->getSubTasks()->map(function(Task $subtask) use(
                $translator
            ) {
                // sub task state
                /* @var $stateHandler TaskStateHandler */
                $stateHandler = $this->get('task_stock.task_state_handler_builder')->build($subtask);
                $subTaskState = $stateHandler->getCurrentState();

                // build data
                $subtaskData = [
                    'id' => $subtask->getId(),
                    'name' => $subtask->getName(),
                    'state' => [
                        'name'  => $subTaskState->getName(),
                        'label' => $translator->trans($subTaskState->getMetadata('label')),
                    ]
                ];

                $assignee = $subtask->getAssignee();
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
            $response['parent'] = [
                'id' => $parentTask->getId(),
                'name' => $parentTask->getName(),
            ];
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/tasks/{id}", name="save_task", requirements={"id": "\d+"})
     * @Route("/tasks", name="save_new_task")
     * @Method({"PUT", "POST"})
     */
    public function saveTaskAction(Request $request, $id = null)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        // get entity manager
        $em = $this->getDoctrine()->getManager();

        // get task
        if (is_numeric($id)) {
            $task = $this
                ->getDoctrine()
                ->getRepository('TaskStockBundle:Task')
                ->find($id);

            if (!$task || $task->isDeleted()) {
                throw new NotFoundHttpException('Task not found');
            }

        } else {
            $task = new Task();
            $task
                ->setDate(new \DateTime())
                ->setOwner($this->getUser());

            //set default state
            $task->setStateName(
                $this
                    ->get('task_stock.task_state_handler_builder')
                    ->build($task)
                    ->getCurrentState()
                    ->getName()
            );

            // subtask
            if ($request->get('parent')) {
                $task->setParent($em->getReference(
                    'TaskStockBundle:Task',
                    $request->get('parent')
                ));
            }

            // set default project
            if (!$this->isGranted(TaskVoter::PERMISSION_CHANGE_PROJECT, $task)) {
                // set default project
                $helpDeskProject = $this->getDoctrine()
                    ->getRepository('TaskStockBundle:TaskProject')
                    ->findOneBy([
                        'code' => $this->container->getParameter('default_project'),
                    ]);

                $task->setProject($helpDeskProject);
            }
        }

        // task change listener
        $taskChangeEvent = new TaskChangeEvent();
        $taskChangeEvent->setUser($this->getUser());
        $task->addPropertyChangedListener($taskChangeEvent);

        // authorize
        if (!$this->isGranted(TaskVoter::PERMISSION_EDIT, $task)) {
            throw $this->createAccessDeniedException();
        }

        // set fields
        $task
            ->setAmount($request->get('amount'))
            ->setCategory(
                $em->getReference(
                    'TaskStockBundle:TaskCategory',
                    $request->get('category')
                )
            )
            ->setDescription($request->get('description'))
            ->setName($request->get('name'))
            ->setLocation($request->get('location'));

        // set assignee
        if ($this->isGranted(TaskVoter::PERMISSION_CHANGE_ASSIGNEE, $task)) {
            if ($request->get('assignee')) {
                $task->setAssignee(
                    $em->getReference(
                        'UserBundle:User',
                        $request->get('assignee')
                    )
                );
            }
        }

        // set assignee
        if ($this->isGranted(TaskVoter::PERMISSION_CHANGE_OWNER, $task)) {
            if ($request->get('owner')) {
                $task->setOwner(
                    $em->getReference(
                        'UserBundle:User',
                        $request->get('owner')
                    )
                );
            }
        }

        // set project
        if ($this->isGranted(TaskVoter::PERMISSION_CHANGE_PROJECT, $task)) {
            if ($request->get('project')) {
                $task->setProject($em->getReference(
                    'TaskStockBundle:TaskProject',
                    $request->get('project')
                ));
            }
        }

        // validate
        $errors = $this->get('validator')->validate($task);
        if (count($errors) > 0) {
            return new JsonResponse([
                'validation' => $this
                    ->get('task_stock.validation_errors_converter')
                    ->constraintViolationListToArray($errors),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // persist
        $em->persist($task);

        // flush
        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // trigger save event
        $this->get('event_dispatcher')->dispatch('task.change', $taskChangeEvent);

        // show response
        return new JsonResponse([
            'id' => $task->getId(),
        ]);
    }

    /**
     * @Route("/tasks/{id}", name="delete_task", requirements={"id": "\d+"})
     * @Method({"DELETE"})
     */
    public function deleteTaskAction(Request $request, $id = null)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        $task = $this->getDoctrine()
            ->getRepository('TaskStockBundle:Task')
            ->find($id);

        if (!$task || $task->isDeleted()) {
            throw new NotFoundHttpException('Task not found');
        }

        // authorize
        if (!$this->isGranted(TaskVoter::PERMISSION_EDIT, $task)) {
            throw $this->createAccessDeniedException();
        }

        // delete
        $task->delete();

        // get entity manager
        $em = $this->getDoctrine()->getManager();
        $em->persist($task);

        // flush
        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/tasks/{id}/state/{transitionName}", name="change_task_state", requirements={"id": "\d+"})
     * @Method({"POST", "PUT"})
     */
    public function setStateAction($id, $transitionName)
    {
        /* @var $task \Sokil\TaskStockBundle\Entity\Task */

        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        // get task
        $task = $this->getDoctrine()
            ->getRepository('TaskStockBundle:Task')
            ->find($id);

        if (!$task || $task->isDeleted()) {
            throw new NotFoundHttpException('Task not found');
        }

        // authorize
        if (!$this->isGranted(TaskVoter::PERMISSION_EDIT, $task)) {
            throw $this->createAccessDeniedException();
        }

        // task change listener
        $taskChangeEvent = new TaskChangeEvent();
        $taskChangeEvent->setUser($this->getUser());
        $task->addPropertyChangedListener($taskChangeEvent);

        // process
        // get state handler
        $stateHandler = $this->get('task_stock.task_state_handler_builder')->build($task);
        $stateHandler->processStateTransition($transitionName);

        // get entity manager
        $em = $this->getDoctrine()->getManager();
        $em->persist($task);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Error while save to database',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // trigger save event
        $this->get('event_dispatcher')->dispatch('task.change', $taskChangeEvent);

        // get state handler
        $stateHandler = $this->get('task_stock.task_state_handler_builder')->build($task);
        $taskState = $stateHandler->getCurrentState();

        // response
        $translator = $this->get('translator');
        return new JsonResponse([
            'state'         => [
                'name'  => $taskState->getName(),
                'label' => $translator->trans($taskState->getMetadata('label')),
            ],
            'nextStates' => array_map(function(\Sokil\State\Transition $transition) use (
                $translator
            ) {
                return [
                    'label' => $translator->trans($transition->getMetadata('label')),
                    'state' => $transition->getResultingStateName(),
                    'icon' => $transition->getMetadata('icon'),
                ];
            }, $stateHandler->getNextStateTransitions()),
        ]);
    }
}
