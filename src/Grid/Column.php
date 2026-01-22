<?php

namespace Kibatic\DatagridBundle\Grid;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatableMessage;

class Column
{
    public string|TranslatableMessage $name;
    public $value;
    private ?string $template;
    public array $templateParameters;
    public ?string $sortable;
    public $sortableQuery;
    public bool $enabled;

    public function __construct(
        string|TranslatableMessage $name,
        string|callable $value = null,
        string $template = null,
        array $templateParameters = [],
        string|array $sortable = null,
        callable|string|null $sortableQuery = null,
        bool $enabled = true,
    ) {
        $this->name = $name;
        $this->value = $value ?? fn($item) => $item;
        $this->template = $template;
        $this->templateParameters = $templateParameters;
        $this->sortable = $sortable;
        $this->sortableQuery = $sortableQuery;
        $this->enabled = $enabled;
    }

    public function getTemplate(null|object|array $entity = null): string
    {
        if ($this->template !== null) {
            return $this->template;
        }

        if ($entity !== null && is_array($this->getValue($entity))) {
            return Template::ARRAY;
        }

        return Template::TEXT;
    }

    public function getValue(object|array $entity)
    {
        if (is_array($entity)) {
            $extra = $entity;
            $entity = $entity[0];
        }

        if (is_callable($this->value)) {
            $valueCallback = $this->value;
            return $valueCallback($entity, $extra ?? []);
        }

        if ($this->value === null) {
            return isset($extra) ? [$entity, $extra] : $entity;
        }

        try {
            return (PropertyAccess::createPropertyAccessor())->getValue($entity, $this->value);
        } catch (NoSuchPropertyException $e) {
            if (isset($extra)) {
                return (PropertyAccess::createPropertyAccessor())->getValue($extra, "[{$this->value}]");
            }

            throw $e;
        }
    }

    public function getTemplateParameter(string $parameterName, ?string $defaultValue = null)
    {
        return $this->templateParameters[$parameterName] ?? $defaultValue;
    }
}
