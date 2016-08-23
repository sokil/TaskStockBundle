<?php

namespace Sokil\TaskStockBundle\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Sokil\TaskStockBundle\Entity\Task;
use Sokil\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Util\ClassUtils;

class TaskVoter implements VoterInterface
{
    const PERMISSION_VIEW               = 'view';
    const PERMISSION_EDIT               = 'edit';
    const PERMISSION_CHANGE_PROJECT     = 'changeProject';
    const PERMISSION_CHANGE_ASSIGNEE    = 'changeAssignee';
    const PERMISSION_CHANGE_OWNER       = 'changeOwner';
    const PERMISSION_ADD_COMMENT        = 'addComment';

    /**
     * @var RoleHierarchyVoter;
     */
    private $roleVoter;

    /**
     * @var TaskProjectVoter
     */
    private $taskProjectVoter;

    public function __construct($roleVoter, TaskProjectVoter $taskProjectVoter)
    {
        $this->roleVoter = $roleVoter;
        $this->taskProjectVoter = $taskProjectVoter;
    }

    public function supportsAttribute($permission)
    {
        return in_array($permission, [
            self::PERMISSION_VIEW,
            self::PERMISSION_EDIT,
            self::PERMISSION_CHANGE_PROJECT,
            self::PERMISSION_CHANGE_ASSIGNEE,
            self::PERMISSION_ADD_COMMENT,
        ]);
    }

    public function supportsClass($class)
    {
        return $class === 'Sokil\TaskStockBundle\Entity\Task';
    }

    public function vote(TokenInterface $token, $task, array $permissions)
    {
        if (!$task || !$this->supportsClass(ClassUtils::getRealClass($task))) {
            return self::ACCESS_ABSTAIN;
        }

        // check if admin
        if(VoterInterface::ACCESS_GRANTED === $this->roleVoter->vote($token, $token->getUser(), array('ROLE_ADMIN'))) {
            return self::ACCESS_GRANTED;
        }

        // abstain vote by default in case none of the attributes are supported
        $vote = self::ACCESS_ABSTAIN;

        foreach ($permissions as $permission) {
            if (!$this->supportsAttribute($permission)) {
                continue;
            }

            // as soon as at least one attribute is supported, default is to deny access
            $vote = self::ACCESS_DENIED;

            $isGranted = call_user_func([$this, 'is' . $permission . 'Granted'] , $task, $token);
            if ($isGranted) {
                // grant access as soon as at least one voter returns a positive response
                return self::ACCESS_GRANTED;
            }
        }

        return $vote;
    }

    protected function isViewGranted(Task $task, TokenInterface $token = null)
    {
        /** @var $permission \Sokil\TaskStockBundle\\Entity\TaskProjectPermission */
        // user can view and edit his tasks
        if ($task->getOwner()->getId() === $token->getUser()->getId()) {
            return true;
        }

        // user can view tasks in projects he belongs if it has permissions
        return TaskProjectVoter::ACCESS_GRANTED === $this->taskProjectVoter->vote(
            $token,
            $task->getProject(),
            [TaskProjectVoter::PERMISSION_TASK_VIEW]
        );
    }

    protected function isEditGranted(Task $task, TokenInterface $token = null)
    {
        // user can view and edit his tasks
        if ($task->getOwner()->getId() === $token->getUser()->getId()) {
            return true;
        }

        // user can edit tasks in projects he belongs if it has permissions
        return TaskProjectVoter::ACCESS_GRANTED === $this->taskProjectVoter->vote(
            $token,
            $task->getProject(),
            [TaskProjectVoter::PERMISSION_TASK_EDIT]
        );
    }

    protected function isChangeProjectGranted(Task $task, TokenInterface $token = null)
    {
        return TaskProjectVoter::ACCESS_GRANTED === $this->taskProjectVoter->vote(
            $token,
            $task->getProject(),
            [TaskProjectVoter::PERMISSION_TASK_CHANGE_PROJECT]
        );
    }

    protected function isChangeAssigneeGranted(Task $task, TokenInterface $token = null)
    {
        return TaskProjectVoter::ACCESS_GRANTED === $this->taskProjectVoter->vote(
            $token,
            $task->getProject(),
            [TaskProjectVoter::PERMISSION_TASK_CHANGE_ASSIGNEE]
        );
    }

    protected function isChangeOwnerGranted(Task $task, TokenInterface $token = null)
    {
        return TaskProjectVoter::ACCESS_GRANTED === $this->taskProjectVoter->vote(
            $token,
            $task->getProject(),
            [TaskProjectVoter::PERMISSION_TASK_CHANGE_OWNER]
        );
    }

    protected function isAddCommentGranted(Task $task, TokenInterface $token = null)
    {
        return TaskProjectVoter::ACCESS_GRANTED === $this->taskProjectVoter->vote(
            $token,
            $task->getProject(),
            [TaskProjectVoter::PERMISSION_TASK_ADD_COMMENT]
        );
    }
}