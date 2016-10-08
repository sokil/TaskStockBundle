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

        // tasks list
        $tasksList = array_map(
            function(Task $task) use($locale) {
                // category
                $category = $task->getCategory();

                /* @var $task \Sokil\TaskStockBundle\Entity\Task */
                return [
                    'id' => $task->getId(),
                    'name' => $task->getName(),
                    'description' => strip_tags($task->getAnnotation()),
                    'date' => $task->getDate('d.m.Y H:i'),
                    'category' => $category ? [
                        'id' => $category->getId(),
                        'name' => $category->getLocalization($locale)->getName(),
                    ] : null,
                    'project' => [
                        'id' => $task->getProject()->getId(),
                        'name' => $task->getProject()->getName(),
                    ],
                    'permissions' => [
                        TaskVoter::PERMISSION_EDIT => $this->isGranted('edit', $task),
                    ],
                ];
            },
            $tasks
        );

        // return response
        return new JsonResponse([
            'tasks' => $tasksList,
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

        $normalizeGroups = [];
        if ($request->get('subtasks')) {
            $normalizeGroups[] = 'withSubdtasks';
        }

        $taskArray = $this
            ->get('task_stock.task_normalizer')
            ->normalize(
                $task,
                null,
                [
                    'groups' => $normalizeGroups,
                ]
            );

        return new JsonResponse($taskArray);
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

            // subtask
            if ($request->get('parent')) {
                $task->setParent($em->getReference(
                    'TaskStockBundle:Task',
                    $request->get('parent')
                ));
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

        // set project
        if ($this->isGranted(TaskVoter::PERMISSION_CHANGE_PROJECT, $task)) {
            $projectId = $request->get('project');
            if ($projectId) {
                $taskProject = $em->getReference(
                    'TaskStockBundle:TaskProject',
                    $projectId
                );
                $task->setProject($taskProject);
            }
        }

        // set state
        if (null === $task->getStateName() ) {
            $stateHandlerBuilder = $this->get('task_stock.task_state_handler_builder');
            if ($task->hasStates()) {
                $stateName = $stateHandlerBuilder
                    ->build($task)
                    ->getCurrentState()
                    ->getName();

                $task->setStateName($stateName);
            }
        }

        // set category
        $categoryId = $request->get('category');
        if ($categoryId) {
            $task->setCategory(
                $em->getReference(
                    'TaskStockBundle:TaskCategory',
                    $categoryId
                )
            );
        }

        // set text
        $task->setName($request->get('name'));
        $task->setDescription($request->get('description'));

        // set assignee
        if ($this->isGranted(TaskVoter::PERMISSION_CHANGE_ASSIGNEE, $task)) {
            $assigneId = $request->get('assignee');
            if ($assigneId) {
                $task->setAssignee($em->getReference('UserBundle:User', $assigneId));
            }
        }

        // set default assignee
        if (null === $task->getAssignee()) {
            $task->setAssignee($this->getUser());
        }

        // set owner
        if ($this->isGranted(TaskVoter::PERMISSION_CHANGE_OWNER, $task)) {
            $ownerId = $request->get('owner');
            if ($ownerId) {
                $task->setOwner($em->getReference('UserBundle:User', $ownerId));
            }
        }

        // set default owner
        if (null === $task->getOwner()) {
            $task->setOwner($this->getUser());
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
