<?php

namespace Kibatic\DatagridBundle\Grid;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatableMessage;

class Column
{
    public string|TranslatableMessage $name;
    /**
     * @var string|\Closure Chemin de propriété, ou callback résolvant la valeur de la ligne.
     */
    public string|\Closure $value;
    private ?string $template;
    public array|\Closure $templateParameters;
    public ?string $sortable;
    /**
     * @var string|\Closure|null Expression DQL de tri, ou callback appliquant le tri au QueryBuilder.
     */
    public string|\Closure|null $sortableQuery;
    public bool $enabled;
    /**
     * @var array Attributs HTML statiques du <th> de la colonne (ex. ['class' => 'num']).
     */
    public array $headerAttr;

    public function __construct(
        string|TranslatableMessage $name,
        string|\Closure|null $value = null,
        ?string $template = null,
        array|\Closure $templateParameters = [],
        string|array|null $sortable = null,
        \Closure|string|null $sortableQuery = null,
        bool $enabled = true,
        array $headerAttr = [],
    ) {
        $this->name = $name;
        $this->value = $value ?? fn($item) => $item;
        $this->template = $template;
        $this->templateParameters = $templateParameters;
        $this->sortable = $sortable;
        $this->sortableQuery = $sortableQuery;
        $this->enabled = $enabled;
        $this->headerAttr = $headerAttr;
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

        if ($this->value instanceof \Closure) {
            $valueCallback = $this->value;

            return $valueCallback($entity, $extra ?? []);
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

    public function getTemplateParameter(
        string $parameterName,
        mixed $defaultValue = null,
        object|array|null $entity = null,
    ): mixed {
        return $this->getTemplateParameters($entity)[$parameterName] ?? $defaultValue;
    }

    /**
     * Résout les paramètres de template pour une ligne donnée. Comme pour la
     * valeur (getValue), si les paramètres sont une closure, elle est invoquée
     * avec l'entité de la ligne et retourne le tableau de paramètres ; sinon le
     * tableau statique est renvoyé tel quel.
     *
     * L'entité n'est facultative que pour des paramètres statiques : une closure
     * n'est pas résolvable hors d'une ligne.
     */
    public function getTemplateParameters(object|array|null $entity = null): array
    {
        if ($this->templateParameters instanceof \Closure) {
            if ($entity === null) {
                $columnName = $this->name instanceof TranslatableMessage ? $this->name->getMessage() : $this->name;

                throw new \LogicException(sprintf(
                    'Colonne "%s" : les paramètres de template sont une closure, ils ne sont pas résolvables sans entité. Passez l\'entité de la ligne, ou utilisez headerAttr pour les attributs du <th>.',
                    $columnName,
                ));
            }

            if (is_array($entity)) {
                $extra = $entity;
                $entity = $entity[0];
            }

            $callback = $this->templateParameters;

            return $callback($entity, $extra ?? []);
        }

        return $this->templateParameters;
    }
}
