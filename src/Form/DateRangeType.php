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
                'label' => 'From',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('end', DateType::class, [
                'label' => 'To',
                'widget' => 'single_text',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DateRange::class,
        ]);
    }
}
