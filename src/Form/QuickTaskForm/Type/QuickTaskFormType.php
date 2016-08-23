<?php

namespace Sokil\TaskStockBundle\Form\QuickTaskForm\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class QuickTaskFormType extends AbstractType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    private $textDomain;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        $textDomain = null
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->textDomain = $textDomain;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $textDomain = $this->textDomain ? $this->textDomain . '.' : null;

        // user
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $builder->add('user', new UserFormType($this->textDomain), [
                'data_class' => 'Sokil\UserBundle\Entity\User',
                'label' => false,
            ]);
        }

        // task
        $builder->add('task', new TaskFormType($this->textDomain), [
            'data_class' => 'Sokil\TaskStockBundle\Entity\Task',
            'label' => false,
        ]);

        // submit button
        $builder->add('submit', 'submit', [
            'label' => $textDomain . 'quicktask_submit',
            'attr' => [
                'class' => 'btn btn-lange btn-success',
            ],
        ]);
    }

    public function getName()
    {
        return 'task_stock_quicktask_form';
    }
}
