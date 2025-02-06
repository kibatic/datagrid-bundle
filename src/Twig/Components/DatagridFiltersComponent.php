<?php

namespace Kibatic\DatagridBundle\Twig\Components;

use Kibatic\DatagridBundle\Grid\Grid;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

final class DatagridFiltersComponent
{
    public FormView $form;
    public Grid $grid;
}
