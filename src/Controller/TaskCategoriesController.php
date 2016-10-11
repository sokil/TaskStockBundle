<?php

namespace Sokil\TaskStockBundle\Controller;

use Sokil\TaskStockBundle\Entity\TaskCategory;
use Sokil\TaskStockBundle\Entity\TaskCategoryLocalization;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskCategoriesController extends Controller
{
    /**
     * @Route("/tasks/categories", name="task_categories_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }
        
        return new JsonResponse([
            'categories' => $this
                ->getDoctrine()
                ->getRepository('TaskStockBundle:TaskCategory')
                ->getAllAsArray($request->getLocale()),
        ]);
    }

    /**
     * @Route("/tasks/categories/{id}", name="task_category")
     * @Method({"GET"})
     */
    public function getAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }
        
        /* @var $taskCategory TaskCategory */
        $taskCategory = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskCategory')
            ->find($id);

        if (!$taskCategory) {
            throw new NotFoundHttpException;
        }

        $taskCategoryArray = $this
            ->get('task_stock.task_category_normalizer')
            ->normalize($taskCategory);
        
        return new JsonResponse($taskCategoryArray);
    }

    /**
     * @Route("/tasks/categories", name="insert_task_category")
     * @Route("/tasks/categories/{id}", name="update_task_category")
     * @Method({"POST", "PUT"})
     */
    public function saveAction(Request $request, $id = null)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }
        

        if ($id) {
            $taskCategory = $this
                ->getDoctrine()
                ->getRepository('TaskStockBundle:TaskCategory')
                ->find($id);

            if (!$taskCategory) {
                throw new NotFoundHttpException();
            }
        } else {
            $taskCategory = new TaskCategory();
        }

        // persist
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();

        try {
            // set state schema
            $stateSchemaId = $request->get('stateSchemaId');
            if (is_numeric($stateSchemaId)) {
                $taskCategory->setStateSchemaId($stateSchemaId);
            }

            // save
            $em->persist($taskCategory);
            $em->flush();
            
            // translated fields
            $localizations = $taskCategory->getLocalizations();
            $name = $request->get('name');
            $description = $request->get('description');
            foreach ($this->container->getParameter('locales') as $locale) {
                // create instance
                if (!isset($localizations[$locale])) {
                    $localizations[$locale] = new TaskCategoryLocalization;
                    $localizations[$locale]
                        ->setTaskCategory($taskCategory)
                        ->setLang($locale);
                }
                // set values
                $localizations[$locale]
                    ->setName(isset($name[$locale]) ? $name[$locale] : null)
                    ->setDescription(isset($description[$locale]) ? $description[$locale] : null);
                // persist
                $em->persist($localizations[$locale]);
            }
            // flush
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();
            return new JsonResponse([
                'error'     => 1,
                'message'   => $e->getMessage(),
            ]);
        }

        return new JsonResponse([
            'error' => 0,
        ]);
    }

    /**
     * @Route("/tasks/categories/{id}", name="delete_task_category")
     * @Method({"DELETE"})
     */
    public function deleteAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }
        
        $taskCategory = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskCategory')
            ->find($id);

        if (!$taskCategory) {
            throw new NotFoundHttpException;
        }
        
        // remove
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($taskCategory);
            $em->flush();
            return new JsonResponse(['error' => 0]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1, 'message' => $e->getMessage()]);
        }

    }
}
