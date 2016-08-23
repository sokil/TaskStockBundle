<?php

namespace Sokil\TaskStockBundle\Form\QuickTaskForm\Handler;

use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Sokil\TaskStockBundle\State\TaskStateHandlerBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Sokil\TaskStockBundle\Entity\Task;
use FOS\UserBundle\Security\LoginManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Sokil\TaskStockBundle\Event\TaskChangeEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QuickTaskFormHandler
{
    protected $requestStack;
    protected $userManager;
    protected $loginManager;
    protected $mailer;
    protected $tokenGenerator;
    protected $registry;
    protected $projectCode;

    protected $eventDispatcher;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    private $taskStateHandlerBuilder;

    public function __construct(
        RequestStack $requestStack,
        UserManagerInterface $userManager,
        LoginManager $loginManager,
        $firewallName,
        Registry $registry,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        $projectCode,
        EventDispatcherInterface $eventDispatcher,
        TaskStateHandlerBuilder $taskStateHandlerBuilder
    ) {
        $this->requestStack = $requestStack;
        $this->userManager = $userManager;
        $this->loginManager = $loginManager;
        $this->firewallName = $firewallName;
        $this->mailer = $mailer;
        $this->tokenGenerator = $tokenGenerator;
        $this->registry = $registry;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->projectCode = $projectCode;
        $this->eventDispatcher = $eventDispatcher;
        $this->taskStateHandlerBuilder = $taskStateHandlerBuilder;
    }
    
    public function handle(FormInterface $form)
    {
        try {
            $isAuthenticated = $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');

            // start transaction
            $this->registry->getConnection()->beginTransaction();

            // create user instance
            if ($isAuthenticated) {
                $token = $this->tokenStorage->getToken();
                if ($token !== null && is_object($token)) {
                    $user = $token->getUser();
                }
            }

            if (!isset($user)) {
                $user = $this->userManager->createUser();
                $form->get('user')->setData($user);
            }

            // task
            $task = new Task();

            // task change listener
            $taskChangeEvent = new TaskChangeEvent();
            $taskChangeEvent->setUser($user);
            $task->addPropertyChangedListener($taskChangeEvent);

            // set data
            $form->get('task')->setData($task);

            //set default state
            $task->setStateName(
                $this
                    ->taskStateHandlerBuilder
                    ->build($task)
                    ->getState()
                    ->getName()
            );

            // pass data from request
            $form->handleRequest($this->requestStack->getCurrentRequest());

            // save user
            if (!$isAuthenticated) {
                // validate
                if (!$form->get('user')->isValid()) {
                    return false;
                }
                // generate random password
                $randomPasswordGenerator = new SecureRandom();
                $randomPassword = $randomPasswordGenerator->nextBytes(8);

                // require user confirm to activate
                $user
                    ->setPlainPassword($randomPassword)
                    ->addRole('ROLE_CLIENT');

                /*
                 * User activation
                 */
                if (true) {
                    $user->setEnabled(true);
                } else {
                    $user->setEnabled(false);
                    if (null === $user->getConfirmationToken()) {
                     $user->setConfirmationToken($this->tokenGenerator->generateToken());
                    }
                    $this->mailer->sendConfirmationEmailMessage($user);
                }

                // save user
                $this->userManager->updateUser($user);

                // authorize
                $this->loginManager->loginUser(
                    $this->firewallName,
                    $user
                );
            }

            // set project
            $project = $this->registry
                ->getRepository('TaskStockBundle:TaskProject')
                ->findOneBy([
                    'code' => $this->projectCode
                ]);

            if (!$project) {
                throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Project not configured');
            }

            $task->setProject($project);

            // save task
            $task
                ->setDate(new \DateTime())
                ->setOwner($user)
                ->setAssignee($user);

            // validate
            if (!$form->get('task')->isValid()) {
                return false;
            }

            $manager = $this->registry->getManager();
            $manager->persist($task);
            $manager->flush();

            // commit transaction
            $this->registry->getConnection()->commit();

        } catch (\Exception $e) {
            // rollback transaction
            $this->registry->getConnection()->rollback();
            return false;
        }

        // trigger save event
        $this->eventDispatcher->dispatch('task.change', $taskChangeEvent);

        return true;
    }
}