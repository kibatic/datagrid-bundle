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
}
