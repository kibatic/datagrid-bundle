<?php

namespace Kibatic\DatagridBundle\Grid;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Column
{
    public string $name;
    public $value;
    private ?string $template;
    public array $templateParameters;
    public ?string $sortable;
    public $sortableQuery;

    public function __construct(
        string $name,
        string|callable $value = null,
        string $template = null,
        array $templateParameters = [],
        string|array $sortable = null,
        callable|string|null $sortableQuery = null
    ) {
        $this->name = $name;
        $this->value = $value ?? fn($item) => $item;
        $this->template = $template;
        $this->templateParameters = $templateParameters;
        $this->sortable = $sortable;
        $this->sortableQuery = $sortableQuery;
    }

    public function getTemplate(?object $entity = null): string
    {
        if ($this->template !== null) {
            return $this->template;
        }

        if ($entity !== null && is_array($this->getValue($entity))) {
            return Template::ARRAY;
        }

        return Template::TEXT;
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
