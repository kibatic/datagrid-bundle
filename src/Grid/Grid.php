<?php

namespace Kibatic\DatagridBundle\Grid;

use Knp\Component\Pager\Pagination\PaginationInterface;

class Grid
{
    /**
     * @var array|Column[]
     */
    private array $columns;
    private array $batchActions;
    private string $theme;
    private $rowAttributesCallback = null;

    private PaginationInterface $pagination;

    public function __construct(
        array $columns,
        PaginationInterface $pagination,
        string $theme,
        array $batchActions = [],
    ) {
        $this->columns = $columns;
        $this->pagination = $pagination;
        $this->batchActions = $batchActions;
        $this->theme = $theme;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getPagination(): PaginationInterface
    {
        return $this->pagination;
    }

    public function getBatchActions(): array
    {
        return $this->batchActions;
    }

    public function hasBatchActions(): bool
    {
        return !empty($this->batchActions);
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getRowAttributes($item, bool $keepAsArray = false): null|array|string
    {
        if (!is_callable($this->rowAttributesCallback)) {
            return null;
        }

        $callback = $this->rowAttributesCallback;
        $attributes = $callback($item);

        if (!is_array($attributes)) {
            return null;
        }

        if ($keepAsArray) {
            return $attributes;
        }

        return self::attributesToHtml($attributes);
    }

    private static function attributesToHtml(array $attributes): string
    {
        return array_reduce(
            array_keys($attributes),
            function (string $carry, string $key) use ($attributes) {
                $value = $attributes[$key];

                if (!\is_scalar($value) && null !== $value) {
                    throw new \LogicException(sprintf('A "%s" prop was passed when creating the component. No matching "%s" property or mount() argument was found, so we attempted to use this as an HTML attribute. But, the value is not a scalar (it\'s a %s). Did you mean to pass this to your component or is there a typo on its name?', $key, $key, get_debug_type($value)));
                }

                if (null === $value) {
                    throw new \Exception('Passing "null" as an attribute value is forbidden');
                }

                return match ($value) {
                    true => "{$carry} {$key}",
                    false => $carry,
                    default => sprintf('%s %s="%s"', $carry, $key, $value),
                };
            },
            ''
        );
    }
}
