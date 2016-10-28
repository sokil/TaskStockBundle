<?php

namespace Sokil\TaskStockBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

class TaskCategoryRepository extends EntityRepository
{
    /**
     * List of all categories with localizations
     * @param $lang
     * @return array
     */
    public function getAllAsArray($lang)
    {
        $query = '
            SELECT tc.id, tcl.name, tcl.description
            FROM TaskStockBundle:TaskCategory tc
            LEFT JOIN TaskStockBundle:TaskCategoryLocalization tcl WITH tcl.taskCategory = tc.id
            WHERE tc.deleted = 0 AND tcl.lang = :lang
            ORDER BY tcl.name
        ';

        return $this->getEntityManager()
            ->createQuery($query)
            ->setParameters([
                'lang'  => $lang,
            ])
            ->getArrayResult();
    }

    /**
     * @param $lang
     * @param $schemaId
     * @return AbstractQuery
     */
    public function getBySchema($lang, $schemaId)
    {
        $query = '
            SELECT tc.id, tcl.name, tcl.description
            FROM TaskStockBundle:TaskCategory tc
            LEFT JOIN TaskStockBundle:TaskCategoryLocalization tcl WITH tcl.taskCategory = tc.id
            WHERE tc.deleted = 0 AND tcl.lang = :lang AND :schemaId MEMBER OF tc.schemas
            ORDER BY tcl.name
        ';

        return $this->getEntityManager()
            ->createQuery($query)
            ->setParameters([
                'lang'  => $lang,
                'schemaId' => $schemaId,
            ]);
    }
}