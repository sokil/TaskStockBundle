<?php

namespace Sokil\TaskStockBundle\Serializer\Normalizer;

use Sokil\TaskStockBundle\Entity\TaskCategorySchema;
use Sokil\TaskStockBundle\Entity\TaskProject;
use Sokil\TaskStockBundle\State\TaskStateHandlerBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class TaskProjectNormalizer implements NormalizerInterface
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

    public function __construct(
        EntityRepository $taskProjectRepository,
        TaskStateHandlerBuilder $taskStateHandlerBuilder,
        AuthorizationChecker $authorizationChecker
    ) {
        $this->taskCategorySchemaRepository = $taskProjectRepository;
        $this->taskStateHandlerBuilder = $taskStateHandlerBuilder;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function normalize(
        $taskProject,
        $format = null,
        array $context = array()
    ) {
        if (!($taskProject instanceof TaskProject)) {
            throw new \InvalidArgumentException('Normalized object must be instance of ' . TaskProject::class);
        }

        // common parameters
        $taskProjectArray = [
            'id'    => $taskProject->getId(),
            'name'   => $taskProject->getName(),
            'code'  => $taskProject->getCode(),
            'description' => $taskProject->getDescription(),
            'permissions' => [
                'edit' => $this->authorizationChecker->isGranted('ROLE_TASK_PROJECT_MANAGER'),
            ],
        ];

        // notification
        $notificationSchemaId = $taskProject->getNotificationSchemaId();
        if ($notificationSchemaId) {
            $taskProjectArray['notificationSchemaId'] = $notificationSchemaId;
        }

        // state configurations
        $stateSchemas = array_map(
            function($stateSchema) {
                return [
                    'id' => $stateSchema['id'],
                    'name' => $stateSchema['name'],
                ];
            },
            $this->taskStateHandlerBuilder->getStateConfigurations()
        );

        if ($stateSchemas) {
            $taskProjectArray['stateSchema'] = [
                'id' => $taskProject->getStateSchemaId(),
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
            $this->taskCategorySchemaRepository->findAll()
        );

        if ($categorySchemaList) {
            $taskProjectArray['categorySchema'] = [
                'id' => $taskProject->getTaskCategorySchemaId(),
                'list' => $categorySchemaList,
            ];
        }

        return $taskProjectArray;
    }

    public function supportsNormalization($data, $format = null)
    {
        return true;
    }
}