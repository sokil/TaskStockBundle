<?php

namespace Sokil\TaskStockBundle\Controller;

use Sokil\TaskStockBundle\Entity\TaskCategorySchema;
use Sokil\TaskStockBundle\Entity\TaskProject;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TaskProjectsController extends Controller
{
    /**
     * @Route("/tasks/projects", name="task_projects_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request)
    {
        /* @var $repository \Doctrine\ORM\EntityRepository */

        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        // query
        $repository = $this->getDoctrine()->getRepository('TaskStockBundle:TaskProject');
        $queryBuilder = $repository
            ->createQueryBuilder('p')
            ->where('p.deleted = 0')
            ->orderBy('p.name');

        // paginator
        $limit = (int) $request->get('limit', 20);
        if($limit > 100) {
            $limit = 100;
        }
        $queryBuilder->setMaxResults($limit);

        $offset = (int) $request->get('offset', 0);
        $queryBuilder->setFirstResult($offset);

        // get list of projects
        $projects = $queryBuilder->getQuery()->getResult();

        // get total count of users
        $paginator = new Paginator($queryBuilder);

        // permissions
        $canEdit = $this->isGranted('ROLE_TASK_PROJECT_MANAGER');

        // return response
        return new JsonResponse([
            'projects' => array_map(function(TaskProject $project) use ($canEdit) {
                return [
                    'id' => $project->getId(),
                    'name' => $project->getName(),
                    'code' => $project->getCode(),
                    'permissions' => [
                        'edit' => $canEdit,
                    ],
                ];
            }, $projects),
            'projectsCount' => $paginator->count(),
        ]);
    }

    /**
     * @Route("/tasks/projects/{id}", name="task_project", requirements={"id": "\d+"})
     * @Method({"GET"})
     */
    public function getAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }
        
        /* @var $project TaskProject */
        $project = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskProject')
            ->find($id);

        if (!$project) {
            throw new NotFoundHttpException;
        }

        // common parameters
        $response = [
            'id'    => $project->getId(),
            'name'   => $project->getName(),
            'code'  => $project->getCode(),
            'description' => $project->getDescription(),
            'permissions' => [
                'edit' => $this->isGranted('ROLE_TASK_PROJECT_MANAGER'),
            ],
        ];

        // notification
        $notificationSchemaId = $project->getNotificationSchemaId();
        if ($notificationSchemaId) {
            $response['notificationSchemaId'] = $notificationSchemaId;
        }

        // state configurations
        $stateSchemas = array_map(
            function($stateSchema) {
                return [
                    'id' => $stateSchema['id'],
                    'name' => $stateSchema['name'],
                ];
            },
            $this
                ->get('task_stock.task_state_handler_builder')
                ->getStateConfigurations()
        );

        if ($stateSchemas) {
            $response['stateSchema'] = [
                'id' => $project->getStateSchemaId(),
                'list' => $stateSchemas,
            ];
        }

        // category schema
        $categorySchemaList = array_map(
            function(TaskCategorySchema $categorySchema) {
                return [
                    'id' => $categorySchema->getId(),
                    'name' => $categorySchema->getName(),
                ];
            },
            $this->getDoctrine()->getRepository('TaskStockBundle:TaskCategorySchema')->findAll()
        );

        if ($categorySchemaList) {
            $response['categorySchema'] = [
                'id' => $project->getTaskCategorySchemaId(),
                'list' => $categorySchemaList,
            ];
        }

        // show response
        return new JsonResponse($response);
    }

    /**
     * @Route("/tasks/projects", name="insert_task_project")
     * @Route("/tasks/projects/{id}", name="update_task_project", requirements={"id": "\d+"})
     * @Method({"POST", "PUT"})
     */
    public function saveAction(Request $request, $id = null)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_PROJECT_MANAGER')) {
            throw $this->createAccessDeniedException();
        }
        

        if ($id) {
            $project = $this
                ->getDoctrine()
                ->getRepository('TaskStockBundle:TaskProject')
                ->find($id);

            if (!$project) {
                throw new NotFoundHttpException;
            }
        } else {
            $project = new TaskProject();
        }


        $project
            ->setName($request->get('name'))
            ->setCode($request->get('code'))
            ->setDescription($request->get('description'));


        $notificationSchemaId = $request->get('notificationSchemaId');
        if (is_numeric($notificationSchemaId)) {
            $project->setNotificationSchemaId($notificationSchemaId);
        }

        $stateSchemaId = $request->get('stateSchemaId');
        if (is_numeric($stateSchemaId)) {
            $project->setStateSchemaId($stateSchemaId);
        }

        $taskСategorySchemaId = $request->get('taskСategorySchemaId');
        if (is_numeric($taskСategorySchemaId)) {
            $project->setTaskCategorySchemaId($taskСategorySchemaId);
        }

        // validate
        $errors = $this->get('validator')->validate($project);
        if (count($errors) > 0) {
            return new JsonResponse([
                'validation' => $this
                    ->get('task_stock.validation_errors_converter')
                    ->constraintViolationListToArray($errors),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // persist
        try {
            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'message'   => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'id' => $project->getId(),
            'permissions' => [
                'edit' => $this->isGranted('ROLE_TASK_PROJECT_MANAGER'),
            ]
        ]);
    }

    /**
     * @Route("/tasks/projects/{id}", name="delete_task_project", requirements={"id": "\d+"})
     * @Method({"DELETE"})
     */
    public function deleteAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_PROJECT_MANAGER')) {
            throw $this->createAccessDeniedException();
        }
        
        $taskProject = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskProject')
            ->find($id);

        if (!$taskProject) {
            throw new NotFoundHttpException;
        }

        // prepare
        $em = $this->getDoctrine()->getManager();
        $taskProject->delete();
        $em->persist($taskProject);

        // remove
        try {
            $em->flush();
            return new JsonResponse(
                null,
                JsonResponse::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['message' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }
}
