<?php

namespace Kibatic\DatagridBundle\Grid;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Column
{
    public string $name;
    public $value;
    public string $template;
    public array $templateParameters;
    public ?string $sortable;

    public function __construct(
        string $name,
        string|callable $value = null,
        string $template = null,
        array $templateParameters = [],
        string $sortable = null
    ) {
        $this->name = $name;
        $this->value = $value ?? fn($item) => $item;
        $this->template = $template ?? Template::TEXT;
        $this->templateParameters = $templateParameters;
        $this->sortable = $sortable;
    }

    public function getValue(object $entity)
    {
        if (is_callable($this->value)) {
            $valueCallback = $this->value;
            return $valueCallback($entity);
        }

        return (PropertyAccess::createPropertyAccessor())->getValue($entity, $this->value);
    }

    public function getTemplateParameter(string $parameterName, $defaultValue = null)
    {
        return $this->templateParameters[$parameterName] ?? $defaultValue;
    }
}
