<?php

namespace Sokil\TaskStockBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use Sokil\FileStorageBundle\Entity\File;
use Sokil\UserBundle\Entity\User;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;

/**
 * @ORM\Table(name="tasks")
 * @ORM\Entity
 */
class Task implements NotifyPropertyChanged
{
    /**
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
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="TaskCategory", fetch="EAGER")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=true)
     * @var TaskCategory
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="TaskProject", fetch="EAGER")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=true)
     * @Assert\NotNull(message="task.project_not_set")
     * @var TaskProject
     */
    protected $project;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Task",
     *   inversedBy="subTasks"
     * )
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @var Task
     */
    protected $parent;

    /**
     * @var Collection
     * @ORM\OneToMany(
     *  targetEntity="Sokil\TaskStockBundle\Entity\Task",
     *  mappedBy="parent",
     *  indexBy="id",
     *  cascade={"remove", "persist"}
     * )
     */
    protected $subTasks;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\NotNull
     * @var \DateTime 
     */
    protected $date;

    /**
     * @ORM\ManyToOne(targetEntity="Sokil\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
     * @Assert\NotNull
     * @var User
     */
    protected $owner;

    /**
     * @ORM\ManyToOne(targetEntity="Sokil\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="assignee_id", referencedColumnName="id", nullable=true)
     * @var User
     */
    protected $assignee;

    /**
     * @ORM\Column(name="state", type="string", nullable=true)
     */
    protected $stateName;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $deleted = false;

    /**
     * @var Collection
     * @ORM\OneToMany(
     *  targetEntity="Sokil\TaskStockBundle\Entity\TaskComment",
     *  mappedBy="task",
     *  indexBy="id",
     *  cascade={"remove", "persist"}
     * )
     */
    protected $comments;

    /**
     * @var Collection
     * @ORM\ManyToMany(
     *      targetEntity="Sokil\FileStorageBundle\Entity\File",
     *      indexBy="id"
     * )
     * @ORM\JoinTable(
     *     name="task_attachments",
     *     joinColumns={@ORM\JoinColumn(name="task_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id")}
     * )
     */
    protected $attachments;

    private $propertyChangedListeners = [];

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->subTasks = new ArrayCollection();
        $this->attachments = new ArrayCollection();
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

    public function getCode()
    {
        return $this->getId();
    }

    public function getStateName()
    {
        return $this->stateName;
    }

    public function setStateName($stateName)
    {
        // notify about property change
        $this->notifyPropertyChanged(
            'stateName',
            $this->stateName,
            $stateName
        );

        $this->stateName = $stateName;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Task
     */
    public function setName($name)
    {
        if ($name !== $this->name) {
            $this->notifyPropertyChanged('name', $this->name, $name);
            $this->name = $name;
        }

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
     * @return Task
     */
    public function setDescription($description)
    {
        if ($description !== $this->description) {
            $this->notifyPropertyChanged('description', $this->description, $description);
            $this->description = strip_tags($description, '<h1><h2><h3><h4><h5><h6><div><p><blockquote><pre><span><sub><sup><br><strong><en><table><thead><tbody><tfoot><tr><th><td><ul><ol><li>');
        }

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

    public function getAnnotation($length = 400)
    {
        if (!$this->description) {
            return null;
        }

        if (strlen($this->description) < $length) {
            return $this->description;
        }

        $annotation = substr($this->description, 0, $length);

        $pos = strrpos($annotation, ' ');
        if ($pos !== false) {
            $annotation = substr($annotation, 0, $pos);
        }

        return $annotation . '...';
    }

    /**
     * Set category
     *
     * @param TaskCategory $category
     * @return Task
     */
    public function setCategory(TaskCategory $category)
    {
        if (!$this->category || $category->getId() !== $this->category->getId()) {
            $this->notifyPropertyChanged('category', $this->category, $category);
            $this->category = $category;
        }

        return $this;
    }

    /**
     * Get project
     *
     * @return TaskProject
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set project
     *
     * @param TaskProject $project
     * @return Task
     */
    public function setProject(TaskProject $project)
    {
        if (!$this->project || $this->project->getId() !== $project->getId()) {
            $this->notifyPropertyChanged('project', $this->project, $project);
            $this->project = $project;
        }

        return $this;
    }

    /**
     * Get category
     *
     * @return TaskCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Task
     */
    public function setDate(\DateTime $date)
    {
        if ($date !== $this->date) {
            $this->notifyPropertyChanged('date', $this->date, $date);
            $this->date = $date;
        }

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate($format = null)
    {
        if (!$this->date) {
            return null;
        }

        return $format ? $this->date->format($format) : $this->date;
    }

    /**
     * Set owner
     *
     * @param User $owner
     * @return Task
     */
    public function setOwner(User $owner = null)
    {
        if (!$this->owner || $this->owner->getId() !== $owner->getId()) {
            $this->notifyPropertyChanged('owner', $this->owner, $owner);
            $this->owner = $owner;
        }

        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set owner
     *
     * @param User $assignee
     * @return Task
     */
    public function setAssignee(User $assignee = null)
    {
        if (!$this->assignee || $this->assignee->getId() !== $assignee->getId()) {
            $this->notifyPropertyChanged('assignee', $this->assignee, $assignee);
            $this->assignee = $assignee;
        }

        return $this;
    }

    /**
     * Get owner
     *
     * @return User
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function addAttachment(File $file)
    {
        $this->attachments[$file->getId()] = $file;
        return $this;
    }

    public function removeAttachment($fileId)
    {
        $this->attachments->remove($fileId);
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

    /**
     * @return Task
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return Task
     */
    public function setParent(Task $task)
    {
        $this->parent = $task;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubTasks()
    {
        return $this->subTasks->matching(Criteria::create()->where(Criteria::expr()->eq('deleted', 0)));
    }

    public function addSubTask(Task $task)
    {
        $this->subTasks[$task->getId()] = $task;
        return $this;
    }

    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->propertyChangedListeners[] = $listener;
        return $this;
    }

    private function notifyPropertyChanged($propName, $oldValue, $newValue)
    {
        if ($this->propertyChangedListeners) {
            foreach ($this->propertyChangedListeners as $listener) {
                $listener->propertyChanged($this, $propName, $oldValue, $newValue);
            }
        }

        return $this;
    }
}
