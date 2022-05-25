<?php

namespace App\Kibatic\DatagridBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'translation_domain' => 'KibaticDatagridBundle',
            'choices' => [
                'column.boolean.true' => true,
                'column.boolean.false' => false,
            ]
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
