<?php

namespace Kibatic\DatagridBundle\Grid;

use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class GridBuilder
{
    private PaginatorInterface $paginator;

    private QueryBuilder $queryBuilder;
    private ?Request $request;
    private ?FormInterface $filtersForm;
    private ?int $itemsPerPage;

    /**
     * @var array|Column[]
     */
    private array $columns = [];
    /**
     * @var array|Filter[]
     */
    private array $filters = [];
    private array $batchActions = [];
    private ?string $theme = '@KibaticDatagrid/theme/bootstrap5';

    private ?Grid $grid;

    public function __construct(PaginatorInterface $paginator, ParameterBagInterface $params)
    {
        $this->paginator = $paginator;
        $this->itemsPerPage = $params->get('knp_paginator.page_limit') ?? 10;
    }

    /**
     * @deprecated
     */
    public function create(QueryBuilder $queryBuilder, Request $request, FormInterface $filtersForm = null): self
    {
        return $this->initialize($request, $queryBuilder, $filtersForm);
    }

    public function initialize(Request $request, QueryBuilder $queryBuilder, FormInterface $filtersForm = null): self
    {
        $this->queryBuilder = $queryBuilder;
        $this->request = $request;
        $this->filtersForm = $filtersForm;

        $this->grid = null;
        $this->columns = [];
        $this->filters = [];

        return $this;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;
        
        return $this;
    }

    /**
     * @param string|null $template #Template
     */
    public function addColumn(
        string $name,
        string|callable $value = null,
        string $template = null,
        array $templateParameters = [],
        string $sortable = null,
        callable|string|null $sortableQuery = null
    ): self {
        $this->columns[] = new Column(
            $name,
            $value,
            $template,
            $templateParameters,
            $sortable,
            $sortableQuery
        );

        return $this;
    }
    
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $name): Column
    {
        foreach ($this->columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }

        throw new \Exception("Column named {$name} not found.");
    }

    public function addFilter(string $formFieldName, callable $callback): self
    {
        $this->filters[] = new Filter($formFieldName, $callback);

        return $this;
    }

    private function applySort()
    {
        $sortBy = $this->request->get('sort_by');
        $direction = $this->request->get('sort_order', 'ASC');

        if ($sortBy === null) {
            return;
        }

        // check if the sortBy param is configured
        foreach ($this->columns as $column) {
            if ($column->sortable !== $sortBy) {
                continue;
            }
            
            if (is_callable($column->sortableQuery)) {
                $sortCallback = $column->sortableQuery;
                $sortCallback($this->queryBuilder, $direction);
                continue;
            }

            if ($column->sortableQuery !== null) {
                $this->queryBuilder->orderBy($column->sortableQuery, $direction);
                continue;
            }

            $this->queryBuilder->orderBy($column->sortable, $direction);
        }
    }

    private function applyFilters()
    {
        if (empty($this->filters) ||
            $this->filtersForm === null
        ) {
            return;
        }

        foreach ($this->filters as $filter) {
            $filterField = $this->filtersForm->get($filter->formFieldName);

            if ($filterField === null) {
                throw new \Exception("Form field named {$filter->formFieldName} not found in the filters form of the datagrid.");
            }

            $filterValue = $filterField->getData();

            if ($filterValue === null) {
                continue;
            }

            $callback = $filter->callback; // TODO ($f->c)()
            $callback($this->queryBuilder, $filterValue, $this->filtersForm);
        }
    }

    public function getGrid(): Grid
    {
        if ($this->grid === null) {
            $this->applySort();
            $this->applyFilters();

            $pagination = $this->paginator->paginate(
                $this->queryBuilder->getQuery(),
                $this->request->query->getInt('page', 1),
                $this->itemsPerPage
            );

            $this->grid = new Grid($this->columns, $pagination, $this->theme, $this->batchActions);
        }

        return $this->grid;
    }

    public function addBatchAction(string $id, string $label, string $url): self
    {
        $this->batchActions[] = [
            'id' => $id,
            'label' => $label,
            'url' => $url
        ];

        return $this;
    }

    public function setItemsPerPage(?int $itemsPerPage): self
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }
}
