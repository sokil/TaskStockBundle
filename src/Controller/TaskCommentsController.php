<?php

namespace Sokil\TaskStockBundle\Controller;

use Sokil\TaskStockBundle\Entity\TaskComment;
use Sokil\TaskStockBundle\Entity\TaskCategoryLocalization;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Doctrine\ORM\Tools\Pagination\Paginator;

class TaskCommentsController extends Controller
{
    /**
     * @Route("/tasks/{id}/comments", name="task_comments_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request, $id)
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

        $comments = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskComment')
            ->findBy(
                [
                    'task' => $task,
                ],
                [
                    'date' => 'DESC',
                ]
            );

        return new JsonResponse([
            'comments' => array_map(function(TaskComment $comment) {
                return [
                    'id' => $comment->getId(),
                    'date' => $comment->getDate()->getTimestamp(),
                    'text' => $comment->getText(),
                    'author' => [
                        'id' => $comment->getAuthor()->getId(),
                        'name' => $comment->getAuthor()->getName(),
                        'gravatar' => $comment->getAuthor()->getGravatarDefaultUrl(),
                    ],
                ];
            }, $comments),
        ]);
    }

    /**
     * @Route("/tasks/comments/{id}", name="task_comment")
     * @Method({"GET"})
     */
    public function getAction(Request $request, $id)
    {
        /* @var $comment \Sokil\TaskStockBundle\Entity\TaskComment */

        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        $comment = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskComment')
            ->find($id);

        if (!$comment) {
            throw new NotFoundHttpException('Comment not found');
        }

        return new JsonResponse([
            'id' => $comment->getId(),
            'authot' => [
                'id' => $comment->getAuthor()->getId(),
                'name' => $comment->getAuthor()->getName(),
                'gravatar' => $comment->getAuthor()->getGravatarDefaultUrl(),
            ],
            'text' => $comment->getText(),
            'date' => $comment->getDate('d.m.Y H:i:s'),
        ]);
    }

    /**
     * @Route("/tasks/{id}/comments", name="insert_task_comment", requirements={"id": "\d+"})
     * @Method({"POST"})
     */
    public function insertAction(Request $request, $id)
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

        // comment
        $comment = new TaskComment();
        $comment
            ->setAuthor($this->getUser())
            ->setDate(new \DateTime())
            ->setTask($task)
            ->setText($request->get('text'));

        // persist
        $em = $this->getDoctrine()->getManager();
        $em->persist($comment);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse([
                'message'   => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'id' => $comment->getId(),
            'date' => $comment->getDate()->getTimestamp(),
            'author' => [
                'id' => $comment->getAuthor()->getId(),
                'name' => $comment->getAuthor()->getName(),
                'gravatar' => $comment->getAuthor()->getGravatarDefaultUrl(),
            ],
            'text' => $comment->getText(),
        ]);
    }

    /**
     * @Route("/tasks/comments/{id}", name="update_task_comment")
     * @Method({"PUT"})
     */
    public function updateAction(Request $request, $id = null)
    {
        /* @var $taskComment \Sokil\TaskStockBundle\Entity\TaskComment */

        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        $taskComment = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskComment')
            ->find($id);

        if (!$taskComment) {
            throw new NotFoundHttpException('Comment not found');
        }

        $taskComment->setText($request->get('text'));

        // persist
        $em = $this->getDoctrine()->getManager();
        $em->persist($taskComment);

        try {
            $em->flush();
        } catch (\Exception $e) {
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
     * @Route("/tasks/comments/{id}", name="delete_task_comment")
     * @Method({"DELETE"})
     */
    public function deleteAction(Request $request, $id)
    {
        // check access
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }
        
        $taskComment = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:TaskComment')
            ->find($id);

        if (!$taskComment) {
            throw new NotFoundHttpException;
        }
        
        // remove
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($taskComment);
            $em->flush();
            return new JsonResponse(['error' => 0]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 1, 'message' => $e->getMessage()]);
        }

    }
}
