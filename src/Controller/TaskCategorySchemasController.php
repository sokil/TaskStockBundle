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
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        $lang = $request->getLocale();

        $schemas = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskCategorySchema')
            ->findAll();

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
}
