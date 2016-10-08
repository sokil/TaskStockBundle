<?php

namespace Sokil\TaskStockBundle\Entity;

use Sokil\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @ORM\Entity
 * @ORM\Table(name="task_projects")
 */
class TaskProject
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=20, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max=20)
     */
    protected $code;

    /**
     * @ORM\Column(type="text", length=20, nullable=false)
     */
    protected $description;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $deleted = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(
     *  targetEntity="Sokil\TaskStockBundle\Entity\TaskProjectPermission",
     *  mappedBy="taskProject"
     * )
     */
    protected $permissions;

    /**
     * @ORM\Column(type="integer", name="notification_schema_id", nullable=true)
     */
    protected $notificationSchemaId;

    /**
     * @ORM\Column(type="integer", name="state_schema_id", nullable=true)
     */
    protected $stateSchemaId;

    /**
     * @ORM\Column(type="integer", name="task_category_schema_id", nullable=true)
     */
    protected $taskCategorySchemaId;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return TaskProject
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
     * Set code
     *
     * @param string $code
     * @return TaskProject
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return TaskProject
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
     * Delete user
     * @return User
     */
    public function delete()
    {
        $this->deleted = true;
        return $this;
    }

    /**
     * Undelete user
     * @return User
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

    /**
     * @return ArrayCollection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    public function setPermission(TaskProjectPermission $permissions)
    {
        $this->permissions[] = $permissions;
        return $this;
    }

    public function getUserPermission(User $user)
    {
        return $this->getPermissions()
            ->matching(
                Criteria::create()->where(Criteria::expr()->eq('user', $user))
            )
            ->first();
    }

    public function getNotificationSchemaId()
    {
        return $this->notificationSchemaId;
    }

    public function setNotificationSchemaId($id)
    {
        if (is_numeric($id)) {
            $this->notificationSchemaId = (int) $id;
        } else {
            $this->notificationSchemaId = null;
        }

        return $this;
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

    public function getTaskCategorySchemaId()
    {
        return $this->taskCategorySchemaId;
    }

    public function setTaskCategorySchemaId($id)
    {
        if (!is_numeric($id)) {
            throw new \InvalidArgumentException('Invalid task category schema passed');
        }

        $this->taskCategorySchemaId = (int) $id;

        return $this;
    }

    public function clearTaskCategorySchema()
    {
        $this->taskCategorySchemaId = null;
        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }

}
