<?php

namespace Sokil\TaskStockBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Sokil\TaskStockBundle\Common\Localization\LocalizedInterface;
use Sokil\TaskStockBundle\Entity\TaskCategoryLocalization;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="Sokil\TaskStockBundle\Repository\TaskCategoryRepository")
 * @ORM\Table(name="task_categories")
 */
class TaskCategory implements LocalizedInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(
     *  targetEntity="Sokil\TaskStockBundle\Entity\TaskCategoryLocalization",
     *  mappedBy="taskCategory",
     *  indexBy="lang",
     *  cascade={"remove", "persist"}
     * )
     */
    protected $localizations;

    /**
     * @ORM\Column(type="integer", name="state_schema_id", nullable=true)
     */
    protected $stateSchemaId;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $deleted = false;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="Sokil\TaskStockBundle\Entity\TaskCategorySchema",
     *     mappedBy="categories"
     * )
     */
    protected $schemas;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->localizations = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }

    /**
     * Add localizations
     *
     * @param TaskCategoryLocalization $localization
     * @return TaskCategory
     */
    public function addLocalization(TaskCategoryLocalization $localization)
    {
        $this->localizations[$localization->getLang()] = $localization;

        return $this;
    }

    /**
     * 
     * @param string $lang
     * @return TaskCategoryLocalization $localization
     */
    public function getLocalization($lang)
    {
        return $this->localizations[$lang];
    }

    /**
     * Remove localizations
     *
     * @param \Sokil\TaskStockBundle\Entity\TaskCategoryLocalization $localization
     */
    public function removeLocalization(TaskCategoryLocalization $localization)
    {
        $this->localizations->removeElement($localization);
    }

    /**
     * Get localizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    public function getStateSchemaId()
    {
        return $this->stateSchemaId;
    }

    public function setStateSchemaId($id)
    {
        if (!is_numeric($id)) {
            throw new \InvalidArgumentException('State schema id must be numeric');
        }

        $this->stateSchemaId = (int) $id;

        return $this;
    }

    public function setNoStateSchema()
    {
        $this->stateSchemaId = null;
        return $this;
    }

    /**
     * Delete user
     * @return Task
     */
    public function delete()
    {
        $this->deleted = true;
        return $this;
    }

    /**
     * Undelete user
     * @return Task
     */
    public function undelete()
    {
        $this->deleted = false;
        return $this;
    }

    /**
     * Check if user is deleted
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }
}
