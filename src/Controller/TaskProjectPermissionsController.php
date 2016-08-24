<?php

namespace Sokil\TaskStockBundle\Controller;

use Sokil\TaskStockBundle\Entity\TaskProjectPermission;
use Sokil\TaskStockBundle\Voter\TaskProjectVoter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TaskProjectPermissionsController extends Controller
{
    /**
     * @Route("/tasks/projects/{projectId}/permissions", name="task_project_permissions_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request, $projectId)
    {
        /* @var $repository \Doctrine\ORM\EntityRepository */

        // get project
        $project = $this->getDoctrine()->getRepository('TaskStockBundle:TaskProject')->find($projectId);
        if (!$project || $project->isDeleted()) {
            throw new NotFoundHttpException;
        }

        // check access
        if (!$this->isGranted(TaskProjectVoter::PERMISSION_USERS_VIEW, $project)) {
            throw $this->createAccessDeniedException();
        }

        // query
        $repository = $this->getDoctrine()->getRepository('TaskStockBundle:TaskProjectPermission');
        $queryBuilder = $repository
            ->createQueryBuilder('p')
            ->where('p.taskProject = :project')->setParameter(':project', $project);

        $name = $request->get('name');
        if ($name) {
            $queryBuilder
                ->innerJoin('p.user', 'u')
                ->andWhere($queryBuilder->expr()->like('u.name', $queryBuilder->expr()->literal($name . '%')));
        }

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

        // all task roles
        $allTaskRoles = array_fill_keys(
            array_keys($this->get('task_stock.voter.task_project_voter')->getRoles()),
            false
        );

        // return response
        return new JsonResponse([
            'permissions' => array_map(function(TaskProjectPermission $permission) use (
                $allTaskRoles
            ) {
                return [
                    'id' => $permission->getId(),
                    'user' => [
                        'id' => $permission->getUser()->getId(),
                        'name' => $permission->getUser()->getName(),
                        'gravatar' => $permission->getUser()->getGravatarDefaultUrl(),
                    ],
                    'roles' => array_fill_keys($permission->getRoles(), true) + $allTaskRoles,
                ];
            }, $projects),
            'permissionsCount' => $paginator->count(),
        ]);
    }

    /**
     * @Route("/tasks/projects/permissions/{id}", name="task_project_permission")
     * @Method({"GET"})
     */
    public function getAction(Request $request, $id)
    {
        /* @var $permission TaskProjectPermission */
        $permission = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskProjectPermission')
            ->find($id);

        if (!$permission) {
            throw new NotFoundHttpException;
        }

        // check access
        $project = $permission->getTaskProject();
        if (!$this->isGranted(TaskProjectVoter::PERMISSION_USERS_VIEW, $project)) {
            throw $this->createAccessDeniedException();
        }

        // all task roles
        $allTaskRoles = array_fill_keys(
            array_keys($this->get('task_stock.voter.task_project_voter')->getRoles()),
            false
        );

        return new JsonResponse([
            'id' => $permission->getId(),
            'user' => [
                'id' => $permission->getUser()->getId(),
                'name' => $permission->getUser()->getName(),
                'gravatar' => $permission->getUser()->getGravatarDefaultUrl(),
            ],
            'roles' => array_fill_keys($permission->getRoles(), true) + $allTaskRoles,
        ]);
    }

    /**
     * @Route("/tasks/projects/{projectId}/permissions",
     *   name="create_task_project_permission",
     *   requirements={"projectId": "\d+"}
     * )
     * @Method({"POST"})
     */
    public function createAction(Request $request, $projectId)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_PROJECT_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        // create permission
        $permission = new TaskProjectPermission();

        // get task project
        $taskProject = $this->getDoctrine()
            ->getRepository('TaskStockBundle:TaskProject')
            ->find((int) $projectId);

        if ($taskProject && !$taskProject->isDeleted()) {
            $permission->setTaskProject($taskProject);
        }

        // get user
        $user = $this->getDoctrine()
            ->getRepository('UserBundle:User')
            ->find((int) $request->get('user'));

        if ($user && !$user->isDeleted()) {
            $permission->setUser($user);
        }

        // set roles
        $roles = $request->get('roles');
        if ($roles && is_array($roles)) {
            $permission->setRoles($roles);
        }

        // validate
        $errors = $this->get('validator')->validate($permission);
        if (count($errors) > 0) {
            return new JsonResponse(
                [
                    'validation' => $this
                        ->get('task_stock.validation_errors_converter')
                        ->constraintViolationListToArray($errors),
                ],
                JsonResponse::HTTP_BAD_REQUEST);
        }

        // persist
        $em = $this->getDoctrine()->getManager();

        try {
            // common fields
            $em->persist($permission);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'message'   => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // all task roles
        $allTaskRoles = array_fill_keys(
            array_keys($this->get('task_stock.voter.task_project_voter')->getRoles()),
            false
        );

        return new JsonResponse([
            'id' => $permission->getId(),
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'gravatar' => $user->getGravatarDefaultUrl(),
            ],
            'roles' => array_fill_keys($permission->getRoles(), true) + $allTaskRoles,
        ]);
    }

    /**
     * @Route("/tasks/projects/permissions/{id}", name="update_task_project_permission")
     * @Method({"PUT"})
     */
    public function updateAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_PROJECT_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        $permission = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskProjectPermission')
            ->find($id);

        if (!$permission) {
            throw new NotFoundHttpException;
        }


        $permission->setRoles($request->get('roles'));

        // persist
        $em = $this->getDoctrine()->getManager();

        try {
            // common fields
            $em->persist($permission);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'message'   => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // all task roles
        $allTaskRoles = array_fill_keys(
            array_keys($this->get('task_stock.voter.task_project_voter')->getRoles()),
            false
        );

        return new JsonResponse([
            'id' => $permission->getId(),
            'roles' => array_fill_keys($permission->getRoles(), true) + $allTaskRoles,
        ]);
    }

    /**
     * @Route("/tasks/projects/permissions/{id}", name="delete_task_project_permission")
     * @Method({"DELETE"})
     */
    public function deleteAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_PROJECT_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        // get permission
        $permission = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskProjectPermission')
            ->find($id);

        if (!$permission) {
            throw new NotFoundHttpException;
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($permission);

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

    /**
     * @Route("/tasks/projects/roles", name="task_project_roles")
     * @Method({"GET"})
     */
    public function rolesAction()
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_PROJECT_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        $roles = array_keys($this->get('task_stock.voter.task_project_voter')->getRoles());
        return new JsonResponse(
            array_map(
                function($role) {
                    return [
                        'name'          => $role,
                        'title'         => $this->get('translator')->trans('task_permission.title.' . $role),
                        'descriptions'  => $this->get('translator')->trans('task_permission.description.' . $role),
                    ];
                },
                $roles
            )
        );
    }
}
