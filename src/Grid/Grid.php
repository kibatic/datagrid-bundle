<?php

namespace Kibatic\DatagridBundle\Grid;

use Kibatic\DatagridBundle\Twig\HtmlExtension;
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
        ?callable $rowAttributesCallback = null,
    ) {
        $this->columns = $columns;
        $this->pagination = $pagination;
        $this->batchActions = $batchActions;
        $this->theme = $theme;
        $this->rowAttributesCallback = $rowAttributesCallback;
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

        return HtmlExtension::attributesToHtml($attributes);
    }
}
