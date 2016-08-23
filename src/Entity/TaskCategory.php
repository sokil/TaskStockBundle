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
     * @ORM\Column(type="string")
     */
    protected $url;

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

    /**
     * Set url
     *
     * @param string $url
     * @return TaskCategory
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
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
}
