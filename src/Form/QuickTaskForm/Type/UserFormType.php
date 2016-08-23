<?php

namespace Sokil\TaskStockBundle\Form\QuickTaskForm\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    private $textDomain;

    public function __construct($textDomain = null)
    {
        $this->textDomain = $textDomain;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $textDomain = $this->textDomain ? $this->textDomain . '.' : null;

        $builder->add('name', 'text', [
            'label' => $textDomain . 'quicktask_form_label_user_name',
            'attr' => [
                'placeholder' => $textDomain . 'quicktask_form_placeholder_user_name',
            ]
        ])
        ->add('email', 'email', [
            'label' => $textDomain . 'quicktask_form_label_user_email',
            'attr' => [
                'placeholder' => $textDomain . 'quicktask_form_placeholder_user_email',
            ]
        ])
        ->add('phone', 'text', [
            'label' => $textDomain . 'quicktask_form_label_user_phone',
            'attr' => [
                'placeholder' => $textDomain . 'quicktask_form_placeholder_user_phone'
            ]
        ]);
    }

    public function getName()
    {
        return 'task_stock_quicktask_user_form';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sokil\UserBundle\Entity\User',
        ));
    }
}