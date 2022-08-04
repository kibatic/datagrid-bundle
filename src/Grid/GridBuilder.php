<?php

namespace Kibatic\DatagridBundle\Grid;

use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class GridBuilder
{
    private PaginatorInterface $paginator;

    private QueryBuilder $queryBuilder;
    private ?Request $request;
    private ?FormInterface $filtersForm;
    private int $itemsPerPage = 10;

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

    public function __construct(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
    }

    public function create(QueryBuilder $queryBuilder, Request $request, FormInterface $filtersForm = null): self
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

    public function addColumn(
        string $name,
        string|callable $value = null,
        string $template = null,
        array $templateParameters = [],
        string $sortable = null
    ): self {
        $this->columns[] = new Column(
            $name,
            $value,
            $template,
            $templateParameters,
            $sortable
        );

        return $this;
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

        $this->queryBuilder->orderBy($sortBy, $direction);
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

    public function setItemsPerPage(int $itemsPerPage): self
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }
}
