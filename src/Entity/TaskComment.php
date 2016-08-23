<?php

namespace Sokil\TaskStockBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\UserBundle\Entity\User;

/**
 * @ORM\Entity()
 * @ORM\Table(name="task_comments")
 */
class TaskComment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sokil\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=false)
     */
    protected $author;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    protected $date;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank
     */
    protected $text;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Task",
     *   inversedBy="comments"
     * )
     * @ORM\JoinColumn(name="task_id", referencedColumnName="id", nullable=false)
     */
    protected $task;

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
     * Set text
     *
     * @return string
     */
    public function setText($text)
    {
        $this->text = $text;
        return $text;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return TaskComment
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

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
     * Set author
     *
     * @param User $author
     * @return TaskComment
     */
    public function setAuthor(User $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set author
     *
     * @param Task $task
     * @return TaskComment
     */
    public function setTask(Task $task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get author
     *
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    public function __toString()
    {
        return $this->id;
    }
}
