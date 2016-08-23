<?php

namespace Sokil\TaskStockBundle\Form\QuickTaskForm\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskFormType extends AbstractType
{
    private $textDomain;

    public function __construct($textDomain = null)
    {
        $this->textDomain = $textDomain;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $textDomain = $this->textDomain ? $this->textDomain . '.' : null;

        // configure builder
        $builder
            ->add('name', 'text', [
                'label' => $textDomain . 'quicktask_form_label_task_name',
                'attr' => [
                    'placeholder' => $textDomain . 'quicktask_form_placeholder_task_name'
                ]
            ])
            ->add('description', 'textarea', [
                'label' => $textDomain . 'quicktask_form_label_task_description',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => $textDomain . 'quicktask_form_placeholder_task_description',
                ]
            ]);
    }

    public function getName()
    {
        return 'task_stock_quicktask_task_form';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Sokil\TaskStockBundle\Entity\Task',
        ));
    }
}