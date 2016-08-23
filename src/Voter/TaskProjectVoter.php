<?php

namespace Sokil\TaskStockBundle\Voter;

use Sokil\TaskStockBundle\Entity\TaskProject;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Sokil\UserBundle\Entity\User;

class TaskProjectVoter implements VoterInterface
{
    const TASK_PROJECT_ROLE_WATCHER = 'ROLE_WATCHER';
    const TASK_PROJECT_ROLE_WORKER  = 'ROLE_WORKER';
    const TASK_PROJECT_ROLE_ADMIN   = 'ROLE_ADMIN';

    const PERMISSION_TASK_VIEW              = 'viewTask';
    const PERMISSION_TASK_EDIT              = 'editTask';
    const PERMISSION_TASK_CHANGE_PROJECT    = 'changeTaskProject';
    const PERMISSION_TASK_CHANGE_ASSIGNEE   = 'changeTaskAssignee';
    const PERMISSION_TASK_CHANGE_OWNER      = 'changeTaskOwner';
    const PERMISSION_TASK_ADD_COMMENT       = 'addTaskComment';
    const PERMISSION_USERS_VIEW             = 'viewUsers';

    /**
     * @var RoleHierarchyVoter;
     */
    private $roleVoter;

    public function __construct($roleVoter)
    {
        $this->roleVoter = $roleVoter;
    }

    public function getRoles()
    {
        return [
            self::TASK_PROJECT_ROLE_ADMIN => [
                self::PERMISSION_TASK_VIEW,
                self::PERMISSION_TASK_EDIT,
                self::PERMISSION_TASK_CHANGE_PROJECT,
                self::PERMISSION_TASK_CHANGE_ASSIGNEE,
                self::PERMISSION_TASK_CHANGE_OWNER,
                self::PERMISSION_TASK_ADD_COMMENT
            ],
            self::TASK_PROJECT_ROLE_WORKER => [
                self::PERMISSION_TASK_VIEW,
                self::PERMISSION_TASK_EDIT,
                self::PERMISSION_TASK_CHANGE_ASSIGNEE,
                self::PERMISSION_TASK_CHANGE_OWNER,
                self::PERMISSION_TASK_ADD_COMMENT
            ],
            self::TASK_PROJECT_ROLE_WATCHER => [
                self::PERMISSION_TASK_VIEW,
                self::PERMISSION_TASK_ADD_COMMENT
            ],
        ];
    }

    public function getPermissions()
    {
        return [
            self::PERMISSION_TASK_VIEW => [],
            self::PERMISSION_TASK_EDIT => [
                self::PERMISSION_TASK_VIEW
            ],
            self::PERMISSION_TASK_CHANGE_PROJECT => [],
            self::PERMISSION_TASK_CHANGE_ASSIGNEE => [
                self::PERMISSION_USERS_VIEW
            ],
            self::PERMISSION_TASK_CHANGE_OWNER => [
                self::PERMISSION_USERS_VIEW
            ],
            self::PERMISSION_TASK_ADD_COMMENT => [],
            self::PERMISSION_USERS_VIEW => []
        ];
    }

    public function supportsAttribute($permission)
    {
        return in_array($permission, array_keys($this->getPermissions()));
    }

    public function supportsClass($class)
    {
        return $class === 'Sokil\TaskStockBundle\Entity\TaskProject';
    }

    public function vote(TokenInterface $token, $project, array $permissions)
    {
        if (!$project || !$this->supportsClass(ClassUtils::getRealClass($project))) {
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

            $isGranted = call_user_func([$this, 'is' . $permission . 'Granted'] , $project, $token);
            if ($isGranted) {
                // grant access as soon as at least one voter returns a positive response
                return self::ACCESS_GRANTED;
            }
        }

        return $vote;
    }

    private function isPermissionGranted($permission, TaskProject $project, User $user)
    {
        $projectUserPermission = $project->getUserPermission($user);
        if (!$projectUserPermission) {
            return false;
        }

        $availableRoles = $this->getRoles();
        $userRoles = $projectUserPermission->getRoles();
        $permissionHierarchy = $this->getPermissions();

        foreach($userRoles as $roleName) {
            if (!isset($availableRoles[$roleName])) {
                continue;
            }


            // check child permissions
            $grantedChildPermissions = $availableRoles[$roleName];
            if (in_array($permission, $grantedChildPermissions)) {
                return true;
            }

            // check parent permissions
            $grantedPermissionsHierarchy = array_intersect_key(
                $permissionHierarchy,
                array_flip($grantedChildPermissions)
            );
            $grantedParentPermissions = call_user_func_array('array_merge', $grantedPermissionsHierarchy);
            if (in_array($permission, $grantedParentPermissions)) {
                return true;
            }
        }

        return false;
    }

    protected function isViewTaskGranted(TaskProject $project, TokenInterface $token = null)
    {
        return $this->isPermissionGranted(self::PERMISSION_TASK_VIEW, $project, $token->getUser());
    }

    protected function isEditTAskGranted(TaskProject $project, TokenInterface $token = null)
    {
        return $this->isPermissionGranted(self::PERMISSION_TASK_EDIT, $project, $token->getUser());
    }

    protected function isChangeTaskProjectGranted(TaskProject $project, TokenInterface $token = null)
    {
        return $this->isPermissionGranted(self::PERMISSION_TASK_CHANGE_PROJECT, $project, $token->getUser());
    }

    protected function isChangeTaskAssigneeGranted(TaskProject $project, TokenInterface $token = null)
    {
        return $this->isPermissionGranted(self::PERMISSION_TASK_CHANGE_ASSIGNEE, $project, $token->getUser());
    }

    protected function isChangeTaskOwnerGranted(TaskProject $project, TokenInterface $token = null)
    {
        return $this->isPermissionGranted(self::PERMISSION_TASK_CHANGE_OWNER, $project, $token->getUser());
    }

    protected function isAddTaskCommentGranted(TaskProject $project, TokenInterface $token = null)
    {
        return $this->isPermissionGranted(self::PERMISSION_TASK_ADD_COMMENT, $project, $token->getUser());
    }

    protected function isViewUsersGranted(TaskProject $project, TokenInterface $token = null)
    {
        return $this->isPermissionGranted(self::PERMISSION_USERS_VIEW, $project, $token->getUser());
    }
}