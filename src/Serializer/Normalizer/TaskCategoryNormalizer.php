<?php

namespace Sokil\TaskStockBundle\Serializer\Normalizer;

use Sokil\TaskStockBundle\Entity\TaskCategory;
use Sokil\TaskStockBundle\State\TaskStateHandlerBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TaskCategoryNormalizer implements NormalizerInterface
{
    /**
     * @var TaskStateHandlerBuilder
     */
    private $taskStateHandlerBuilder;

    private $locales;

    public function __construct(
        TaskStateHandlerBuilder $taskStateHandlerBuilder,
        array $locales
    ) {
        $this->taskStateHandlerBuilder = $taskStateHandlerBuilder;
        $this->locales = $locales;
    }

    public function normalize(
        $taskCategory,
        $format = null,
        array $context = array()
    ) {
        if (!($taskCategory instanceof TaskCategory)) {
            throw new \InvalidArgumentException('Normalized object must be instance of ' . TaskCategory::class);
        }

        // common parameters
        $taskCategoryArray = [
            'id'    => $taskCategory->getId(),
        ];

        // state schema
        $taskCategoryArray['stateSchema'] = $this->getStateSchema($taskCategory);

        // localized data
        $localizations = $taskCategory->getLocalizations();

        foreach($this->locales as $locale) {
            if (isset($localizations[$locale])) {
                $taskCategoryArray['name'][$locale] = $localizations[$locale]->getName();
                $taskCategoryArray['description'][$locale] = $localizations[$locale]->getDescription();
            } else {
                $taskCategoryArray['name'][$locale] = '';
                $taskCategoryArray['description'][$locale] = '';
            }
        }

        return array_filter($taskCategoryArray);
    }

    public function supportsNormalization($data, $format = null)
    {
        return true;
    }

    private function getStateSchema(TaskCategory $taskCategory)
    {
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

        if (!$stateSchemas) {
            return null;
        }

        return [
            'id' => $taskCategory->getStateSchemaId(),
            'list' => $stateSchemas,
        ];
    }
}

