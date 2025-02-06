<?php

namespace Kibatic\DatagridBundle\Twig\Components;

use Kibatic\DatagridBundle\Grid\Grid;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

final class DatagridComponent
{
    public ?Grid $grid = null;
    public ?FormView $form = null;
}
