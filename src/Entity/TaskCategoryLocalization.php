<?php

namespace Sokil\TaskStockBundle\Entity;

use Sokil\TaskStockBundle\Common\Localization\LocalizationInterface;
use Sokil\TaskStockBundle\Entity\TaskCategory;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="task_categories_local")
 */
class TaskCategoryLocalization implements LocalizationInterface
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="\Sokil\TaskStockBundle\Entity\TaskCategory", inversedBy="localizations")
     * @ORM\JoinColumn(name="task_category_id", referencedColumnName="id")
     * @var TaskCategory
     */
    protected $taskCategory;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=5, nullable=false)
     */
    protected $lang;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=600, nullable=false)
     */
    protected $description;

    /**
     * Set name
     *
     * @param string $name
     * @return TaskCategoryLocalization
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return TaskCategoryLocalization
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set lang
     *
     * @param string $lang
     * @return TaskCategoryLocalization
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string 
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set taskCategory
     *
     * @param \Sokil\TaskStockBundle\Entity\TaskCategory $taskCategory
     * @return TaskCategoryLocalization
     */
    public function setTaskCategory(\Sokil\TaskStockBundle\Entity\TaskCategory $taskCategory)
    {
        $this->taskCategory = $taskCategory;

        return $this;
    }

    /**
     * Get taskCategory
     *
     * @return \Sokil\TaskStockBundle\Entity\TaskCategory 
     */
    public function getTaskCategory()
    {
        return $this->taskCategory;
    }

    public function __toString()
    {
        return $this->name;
    }
}
