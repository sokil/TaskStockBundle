<?php

namespace Sokil\TaskStockBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Sokil\TaskStockBundle\Entity\TaskProject;
use Sokil\UserBundle\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="task_project_permissions")
 */
class TaskProjectPermission
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Sokil\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @Assert\NotBlank
     */
    protected $user;

    /**
     * @var TaskProject
     * @ORM\ManyToOne(targetEntity="Sokil\TaskStockBundle\Entity\TaskProject", inversedBy="permissions")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     * @Assert\NotBlank()
     */
    protected $taskProject;

    /**
     * @var array
     * @ORM\Column(type="array", nullable=false)
     * @Assert\NotBlank
     */
    protected $roles = [];

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
     * Set role
     *
     * @param string $role
     * @return TaskProjectPermission
     */
    public function addRole($role)
    {
        if (in_array($role, $this->roles)) {
            return $this;
        }

        $this->roles[] = strtoupper($role);

        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Replace roles with new values
     * @param array $roles
     * @return TaskProjectPermission
     */
    public function setRoles(array $roles)
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return TaskProjectPermission
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set taskProject
     *
     * @param TaskProject $taskProject
     * @return TaskProjectPermission
     */
    public function setTaskProject(TaskProject $taskProject)
    {
        $this->taskProject = $taskProject;

        return $this;
    }

    /**
     * Get taskProject
     *
     * @return TaskProject
     */
    public function getTaskProject()
    {
        return $this->taskProject;
    }
}
