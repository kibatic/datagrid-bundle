<?php

namespace Kibatic\DatagridBundle\Controller;

use Symfony\Component\Form\FormBuilderInterface;

trait DatagridControllerHelper
{
    public function createFilterFormBuilder(string $name = 'filters', string $method = 'GET', bool $csrfProtection = false, array $options = []): FormBuilderInterface
    {
        return $this->container->get('form.factory')
            ->createNamedBuilder($name, options: array_merge([
                'method' => $method,
                'csrf_protection' => $csrfProtection,
            ], $options));
    }
}
