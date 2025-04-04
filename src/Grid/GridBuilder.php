<?php

namespace Kibatic\DatagridBundle\Grid;

use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class GridBuilder
{
    private QueryBuilder $queryBuilder;
    private ?Request $request;
    private ?FormInterface $filtersForm;
    private ?int $defaultItemsPerPage;
    private ?int $itemsPerPage;
    private $rowAttributesCallback = null;

    /**
     * @var array|Column[]
     */
    private array $columns = [];
    /**
     * @var array|Filter[]
     */
    private array $filters = [];
    private array $batchActions = [];
    private ?string $batchMethod = 'POST';
    private ?string $theme = '@KibaticDatagrid/theme/bootstrap5';
    private ?string $explicitRouteName = null;
    private array $explicitRouteParams = [];

    private ?Grid $grid;

    public function __construct(
        protected readonly RequestStack $requestStack,
        protected PaginatorInterface $paginator,
        ParameterBagInterface $params
    ) {
        $this->paginator = $paginator;
        $this->defaultItemsPerPage = $params->get('knp_paginator.page_limit') ?? 25;
    }

    /**
     * @deprecated
     */
    public function create(QueryBuilder $queryBuilder, Request $request, ?FormInterface $filtersForm = null): self
    {
        return $this->initialize($request, $queryBuilder, $filtersForm);
    }

    public function initialize(QueryBuilder $queryBuilder, ?FormInterface $filtersForm = null, ?Request $request = null): self
    {
        $request ??= $this->requestStack->getMainRequest();

        if ($filtersForm !== null
            && !$filtersForm->isSubmitted()
        ) {
            $filtersForm?->handleRequest($request);
        }

        $this->request = $request;
        $this->queryBuilder = $queryBuilder;
        $this->filtersForm = $filtersForm;

        $this->reset();

        return $this;
    }

    public function reset()
    {
        $this->itemsPerPage = $this->defaultItemsPerPage;
        $this->rowAttributesCallback = null;
        $this->columns = [];
        $this->filters = [];
        $this->batchActions = [];
        $this->batchMethod = 'POST';
        $this->theme = '@KibaticDatagrid/theme/bootstrap5';
        $this->explicitRouteName = null;
        $this->explicitRouteParams = [];
        $this->grid = null;
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
        string|callable|null $value = null,
        string $template = null,
        array $templateParameters = [],
        string $sortable = null,
        callable|string|null $sortableQuery = null,
        bool $enabled = true,
    ): self {
        $this->columns[] = new Column(
            $name,
            $value,
            $template,
            $templateParameters,
            $sortable,
            $sortableQuery,
            $enabled,
        );

        return $this;
    }
    
    public function getColumns(): array
    {
        return array_filter($this->columns, fn(Column $column) => $column->enabled);
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

    public function removeColumn(string $name): self
    {
        foreach ($this->columns as $key => $column) {
            if ($column->name === $name) {
                unset($this->columns[$key]);
            }
        }

        return $this;
    }

    public function addFilter(string $formFieldName, callable $callback, bool $enabled = true): self
    {
        $this->filters[] = new Filter($formFieldName, $callback, $enabled);

        return $this;
    }

    public function removeFilter(string $formFieldName): self
    {
        foreach ($this->filters as $key => $filter) {
            if ($filter->formFieldName === $formFieldName) {
                unset($this->filters[$key]);
            }
        }

        return $this;
    }

    private function applySort(): void
    {
        $sortBy = $this->request->get('sort_by');
        $direction = $this->request->get('sort_order', 'ASC');

        if ($sortBy === null) {
            return;
        }

        // check if the sortBy param is configured
        foreach ($this->columns as $column) {
            if (!$column->enabled) {
                continue;
            }

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

    public function applyFilters(): void
    {
        if (empty($this->filters) ||
            $this->filtersForm === null
        ) {
            return;
        }

        foreach ($this->filters as $filter) {
            if (!$filter->enabled) {
                continue;
            }

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

    public function addBatchAction(string $id, string $label, string $url): self
    {
        $this->batchActions[] = [
            'id' => $id,
            'label' => $label,
            'url' => $url
        ];

        return $this;
    }

    public function setBatchMethod(string $method): self
    {
        $this->batchMethod = $method;

        return $this;
    }

    public function setItemsPerPage(?int $itemsPerPage): self
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    public function setExplicitRoute(string $routeName, array $routeParams = []): self
    {
        $this->explicitRouteName = $routeName;
        $this->explicitRouteParams = $routeParams;

        return $this;
    }

    public function setRowAttributesCallback(callable $callback): self
    {
        $this->rowAttributesCallback = $callback;

        return $this;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getGrid(bool $forceRecreate = false): Grid
    {
        if ($this->grid === null || $forceRecreate) {
            $this->applySort();
            $this->applyFilters();

            $pagination = $this->paginator->paginate(
                $this->queryBuilder->getQuery(),
                $this->request->query->getInt('page', 1),
                $this->itemsPerPage
            );

            if ($this->explicitRouteName) {
                $pagination->setUsedRoute($this->explicitRouteName);

                foreach ($this->explicitRouteParams as $key => $value) {
                    $pagination->setParam($key, $value);
                }
            }

            $this->grid = new Grid(
                $this->getColumns(),
                $this->request,
                $pagination,
                $this->theme,
                $this->batchActions,
                $this->batchMethod,
                $this->rowAttributesCallback
            );
        }

        return $this->grid;
    }
}
