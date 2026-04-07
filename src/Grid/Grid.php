<?php

namespace Kibatic\DatagridBundle\Grid;

use Kibatic\DatagridBundle\Twig\AppExtension;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\HttpFoundation\Request;

class Grid
{
    /**
     * @var array|Column[]
     */
    private array $columns;
    private array $batchActions;
    private string $batchActionsTokenId;
    private string $batchMethod;
    private string $theme;
    private $rowAttributesCallback = null;
    private array $filterLayout;

    private Request $request;
    private PaginationInterface $pagination;

    public function __construct(
        array $columns,
        Request $request,
        PaginationInterface $pagination,
        string $theme,
        array $batchActions = [],
        string $batchMethod = 'POST',
        string $batchActionsTokenId,
        ?callable $rowAttributesCallback = null,
        array $filterLayout = [],
    ) {
        $this->columns = $columns;
        $this->request = $request;
        $this->pagination = $pagination;
        $this->batchActions = $batchActions;
        $this->batchMethod = $batchMethod;
        $this->batchActionsTokenId = $batchActionsTokenId;
        $this->theme = $theme;
        $this->rowAttributesCallback = $rowAttributesCallback;
        $this->filterLayout = $filterLayout;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getRequest(): Request
    {
        return $this->request;
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

    public function getBatchMethod(): string
    {
        return $this->batchMethod;
    }

    public function getBatchActionsTokenId(): string
    {
        return $this->batchActionsTokenId;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getFilterLayout(): array
    {
        return $this->filterLayout;
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

        return AppExtension::attributesToHtml($attributes);
    }
}
