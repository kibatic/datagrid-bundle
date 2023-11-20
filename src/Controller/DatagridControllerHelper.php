<?php

namespace Kibatic\DatagridBundle\Controller;

use Symfony\Component\Form\FormBuilderInterface;

trait DatagridControllerHelper
{
    public function createFilterFormBuilder(string $name = '', string $method = 'GET', bool $csrfProtection = false): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createNamedBuilder($name, options: [
            'method' => $method,
            'csrf_protection' => $csrfProtection,
        ]);
    }
}
