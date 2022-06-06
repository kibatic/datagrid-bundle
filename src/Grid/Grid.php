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

    private PaginationInterface $pagination;

    public function __construct(
        array $columns,
        PaginationInterface $pagination,
        array $batchActions = []
    ) {
        $this->columns = $columns;
        $this->pagination = $pagination;
        $this->batchActions = $batchActions;
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
}
