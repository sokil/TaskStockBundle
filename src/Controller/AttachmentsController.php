<?php

namespace Sokil\TaskStockBundle\Controller;

use Sokil\FileStorageBundle\Entity\File;
use Sokil\FileStorageBundle\GaufretteAdapter\CloudFileInterface;
use Sokil\FileStorageBundle\GaufretteAdapter\LocalFileInterface;
use Sokil\TaskStockBundle\Voter\TaskVoter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttachmentsController extends Controller
{
    /**
     * @Route("/tasks/{id}/attachments", name="task_attachments_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request, $id)
    {
        // authenticate
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
        if (!$this->isGranted(TaskVoter::PERMISSION_VIEW, $task)) {
            throw $this->createAccessDeniedException();
        }

        return new JsonResponse([
            'attachments' => array_map(
                function(File $file) {
                    return [
                        'id' => $file->getId(),
                        'name' => $file->getName(),
                        'size'  => $file->getSize(),
                        'createdAt' => $file->getCreatedAt()->getTimestamp(),
                        'path' => $this->generateUrl('download_attachment', [
                            'attachmentId' => $file->getId()
                        ]),
                    ];
                },
                array_values($task->getAttachments()->toArray())
            ),
        ]);
    }

    /**
     * @Route("/tasks/{id}/attachments", name="task_attachments_attach")
     * @Method({"POST"})
     */
    public function attachAction(Request $request, $id)
    {
        // authenticate
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        // get task
        $task = $this->getDoctrine()->getRepository('TaskStockBundle:Task')->find($id);
        if (!$task || $task->isDeleted()) {
            throw new NotFoundHttpException('Task not found');
        }

        // authorize
        if (!$this->isGranted(TaskVoter::PERMISSION_VIEW, $task)) {
            throw $this->createAccessDeniedException();
        }

        // upload file
        $uploadHandler = $this->get('task_stock.attachments_upload_handler');
        $uploadedFile = $uploadHandler->getFile();

        // create file
        $file = new File();
        $file
            ->setName($uploadedFile->getOriginalBasename())
            ->setMime($uploadedFile->getType())
            ->setSize($uploadedFile->getSize())
            ->setHash($uploadedFile->getMd5Sum())
            ->setCreatedAt(new \DateTimeImmutable());

        // write file
        $file = $this
            ->get('file_storage')
            ->write(
                $file,
                $this->getParameter('task_stock.attachments_filesystem'),
                file_get_contents($uploadedFile->getPath())
            );

        // add link to task
        $task->addAttachment($file);

        // persist task
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($task);
        $entityManager->flush();

        return new JsonResponse([
            'error' => 0,
            'attachment' => [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'size'  => $file->getSize(),
                'createdAt' => $file->getCreatedAt()->getTimestamp(),
                'path' => $this->generateUrl('download_attachment', [
                    'attachmentId' => $file->getId()
                ]),
            ],
        ]);
    }

    /**
     * @Route(
     *  "/tasks/{taskId}/attachments/{attachmentId}",
     *  name="delete_attachment",
     *  requirements={"taskId": "\d+", "attachmentId": "\d+"}
     * )
     * @Method({"DELETE"})
     */
    public function deleteAction(Request $request, $taskId, $attachmentId)
    {
        // authenticate
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        // get task
        /* @var $task \Sokil\TaskStockBundle\\Entity\Task */
        $task = $this
            ->getDoctrine()
            ->getRepository('TaskStockBundle:Task')
            ->find($taskId);

        if (!$task || $task->isDeleted()) {
            throw new NotFoundHttpException('Task not found');
        }

        // authorize
        if (!$this->isGranted(TaskVoter::PERMISSION_VIEW, $task)) {
            throw $this->createAccessDeniedException();
        }

        // drop attachment
        $task->removeAttachment($attachmentId);

        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        // show responsse
        return new JsonResponse([
            'error' => 0,
        ]);
    }

    /**
     * @Route(
     *  "/tasks/attachments/file/{attachmentId}",
     *  name="download_attachment",
     *  requirements={"attachmentId": "\d+"}
     * )
     * @Method({"GET"})
     */
    public function downloadAction($attachmentId)
    {
        // authenticate
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw $this->createAccessDeniedException();
        }

        // get attachment
        $file = $this
            ->get('file_storage')
            ->getMetadata($attachmentId);

        $filesystem = $this
            ->get('knp_gaufrette.filesystem_map')
            ->get($file->getFilesystem());

        $filesystemAdapter = $filesystem->getAdapter();

        if ($filesystemAdapter instanceof LocalFileInterface) {
            // get absolute path
            $absolutePath = $filesystemAdapter->getPath($attachmentId);

            // get relative path
            $relativePath = str_replace(
                $this->getParameter('kernel.root_dir'),
                '',
                $absolutePath
            );

            // return header
            return new Response(null, 200, [
                'X-Accel-Redirect' => $relativePath,
                'Content-type' => $file->getMime(),
                'Content-Disposition' => 'attachment; filename="' . $file->getName() . '"',
            ]);
        }

        if ($filesystemAdapter instanceof CloudFileInterface) {
            // get file
            return new Response('Adapter currently not supported', 501);
        }
    }
}