<?php

namespace Sokil\TaskStockBundle\Repository;

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
        return $this->getEntityManager()
            ->createQuery('
                SELECT tc.id, tc.url, tcl.name, tcl.description
                FROM TaskStockBundle:TaskCategory tc
                LEFT JOIN TaskStockBundle:TaskCategoryLocalization tcl WITH tcl.taskCategory = tc.id
                WHERE tcl.lang = :lang
                ORDER BY tcl.name
            ')
            ->setParameters([
                'lang'  => $lang,
            ])
            ->getArrayResult();
    }
}