<?php

namespace Sokil\TaskStockBundle\Controller;

use Sokil\TaskStockBundle\Entity\TaskCategory;
use Sokil\TaskStockBundle\Entity\TaskCategoryLocalization;
use Sokil\TaskStockBundle\Entity\TaskCategorySchema;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskCategorySchemasController extends Controller
{
    /**
     * @Route("/tasks/categorySchemas", name="task_category_schemas_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        $lang = $request->getLocale();

        $schemas = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskCategorySchema')
            ->findBy([
                'deleted' => 0,
            ]);

        return new JsonResponse([
            'schemas' => array_map(function(TaskCategorySchema $schema) use ($lang) {
                return [
                    'id' => $schema->getId(),
                    'name' => $schema->getName(),
                    'categories' => array_map(function(TaskCategory $category) use ($lang) {
                        return [
                            'id' => $category->getId(),
                            'stateSchemaId' => $category->getStateSchemaId(),
                            'name' => $category->getLocalization($lang)
                        ];
                    }, $schema->getCategories()->toArray()),
                ];
            }, $schemas),
        ]);
    }

    /**
     * @Route("/tasks/categorySchemas/{id}", name="task_category_schema")
     * @Method({"GET"})
     */
    public function getAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        /* @var $taskCategory TaskCategorySchema */
        $taskCategorySchema = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskCategorySchema')
            ->find($id);

        if (!$taskCategorySchema) {
            throw new NotFoundHttpException;
        }

        $lang = $request->getLocale();

        return new JsonResponse([
            'id' => $taskCategorySchema->getId(),
            'name' => $taskCategorySchema->getName(),
            'categories' => array_map(function(TaskCategory $category) use ($lang) {
                return [
                    'id' => $category->getId(),
                    'stateSchemaId' => $category->getStateSchemaId(),
                    'name' => $category->getLocalization($lang)
                ];
            }, $taskCategorySchema->getCategories()->toArray()),
        ]);
    }

    /**
     * @Route("/tasks/categorySchemas", name="insert_task_category_schema")
     * @Route("/tasks/categorySchemas/{id}", name="update_task_category_schema")
     * @Method({"POST", "PUT"})
     */
    public function saveAction(Request $request, $id = null)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_MANAGER')) {
            throw $this->createAccessDeniedException();
        }


        if ($id) {
            $taskCategorySchema = $this
                ->getDoctrine()
                ->getRepository('TaskStockBundle:TaskCategorySchema')
                ->find($id);

            if (!$taskCategorySchema) {
                throw new NotFoundHttpException();
            }
        } else {
            $taskCategorySchema = new TaskCategorySchema();
        }

        // set data
        $name = $request->get('name');
        $taskCategorySchema->setName($name);

        // save
        $em = $this->getDoctrine()->getManager();
        $em->persist($taskCategorySchema);
        $em->flush();

        return new JsonResponse([
            'error' => 0,
            'id' => $taskCategorySchema->getId(),
        ]);
    }

    /**
     * @Route("/tasks/categorySchemas/{id}", name="delete_task_category_schema")
     * @Method({"DELETE"})
     */
    public function deleteAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        $taskCategorySchema = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskCategorySchema')
            ->find($id);

        if (!$taskCategorySchema) {
            throw new NotFoundHttpException;
        }

        // delete
        $taskCategorySchema->delete();

        // persist
        $em = $this->getDoctrine()->getManager();
        $em->persist($taskCategorySchema);

        // flush
        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }

        return new JsonResponse(['error' => 0]);
    }

    /**
     * @Route(
     *     "/tasks/categorySchemas/{schemaId}/categories",
     *     name="task_category_schema_categories_add"
     * )
     * @Method({"POST", "PUT"})
     */
    public function addCategoriesAction(
        Request $request,
        $schemaId
    ) {
        // check access
        if (!$this->isGranted('ROLE_TASK_MANAGER')) {
            throw $this->createAccessDeniedException();
        }

        $taskCategorySchema = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskCategorySchema')
            ->find($schemaId);

        if (!$taskCategorySchema) {
            throw new NotFoundHttpException;
        }

        $categoryIds = $request->get('categories');
        if (!$categoryIds || !is_array($categoryIds)) {
            throw new BadRequestHttpException('Category ids not specified');
        }

        $entityManager = $this->getDoctrine()->getManager();

        foreach ($categoryIds as $categoryId) {
            $taskCategorySchema->addCategory(
                $entityManager->getReference(
                    'TaskStockBundle:TaskCategory',
                    $categoryId
                )
            );
        }

        try {
            $entityManager->persist($taskCategorySchema);
            $entityManager->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }

        return new JsonResponse([
            'error' => 0,
        ]);
    }

    public function deleteCategoriesAction($schemaId)
    {
        // check access
        if (!$this->isGranted('ROLE_TASK_MANAGER')) {
            throw $this->createAccessDeniedException();
        }
    }
}
