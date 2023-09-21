<?php

namespace Kibatic\DatagridBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Kibatic\DatagridBundle\Dto\DateRange;

class DateRangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', DateType::class, [
                'label' => $options['start_label'],
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('end', DateType::class, [
                'label' => $options['end_label'],
                'widget' => 'single_text',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DateRange::class,
            'start_label' => 'From',
            'end_label' => 'To',
        ]);
    }
}
