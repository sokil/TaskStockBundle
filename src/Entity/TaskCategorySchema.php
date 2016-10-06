<?php

namespace Sokil\TaskStockBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="task_category_schemas")
 */
class TaskCategorySchema
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\ManyToMany(
     *  targetEntity="Sokil\TaskStockBundle\Entity\TaskCategory",
     *  indexBy="id",
     * )
     * @ORM\JoinTable(
     *     name="task_category_schema_categories",
     *     joinColumns={@ORM\JoinColumn(name="schema_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id")}
     * )
     */
    protected $categories;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Add categories
     *
     * @param TaskCategory $category
     * @return TaskCategorySchema
     */
    public function addCategory(TaskCategory $category)
    {
        $this->categories[$category->getId()] = $category;

        return $this;
    }

    /**
     * 
     * @param string $categoryId
     * @return TaskCategory $category
     */
    public function getCategory($categoryId)
    {
        return $this->categories[$categoryId];
    }

    /**
     * Remove categories
     *
     * @param TaskCategory $category
     */
    public function removeCategory(TaskCategory $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }
}
