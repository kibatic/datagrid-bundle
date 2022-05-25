<?php

namespace App\Kibatic\DatagridBundle\Grid;

class Column
{
    public string $name;
    public $valueCallback;
    public string $template;
    public array $templateParameters;
    public ?string $sortable;

    public function __construct(
        string $name,
        callable $valueCallback = null,
        string $template = null,
        array $templateParameters = [],
        string $sortable = null
    ) {
        $this->name = $name;
        $this->valueCallback = $valueCallback ?? fn($item) => $item;
        $this->template = $template ?? Template::TEXT;
        $this->templateParameters = $templateParameters;
        $this->sortable = $sortable;
    }

    public function getValue(object $entity)
    {
        $valueCallback = $this->valueCallback;
        return $valueCallback($entity);
    }

    public function getTemplateParameter(string $parameterName, $defaultValue = null)
    {
        return $this->templateParameters[$parameterName] ?? $defaultValue;
    }
}
