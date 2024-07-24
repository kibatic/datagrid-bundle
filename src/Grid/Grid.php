<?php

namespace Kibatic\DatagridBundle\Grid;

use Kibatic\DatagridBundle\Twig\HtmlExtension;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\HttpFoundation\Request;

class Grid
{
    /**
     * @var array|Column[]
     */
    private array $columns;
    private array $batchActions;
    private string $batchMethod;
    private string $theme;
    private $rowAttributesCallback = null;

    private Request $request;
    private PaginationInterface $pagination;

    public function __construct(
        array $columns,
        Request $request,
        PaginationInterface $pagination,
        string $theme,
        array $batchActions = [],
        string $batchMethod = 'POST',
        ?callable $rowAttributesCallback = null,
    ) {
        $this->columns = $columns;
        $this->request = $request;
        $this->pagination = $pagination;
        $this->batchActions = $batchActions;
        $this->batchMethod = $batchMethod;
        $this->theme = $theme;
        $this->rowAttributesCallback = $rowAttributesCallback;
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
        return json_encode($this->getBatchActions());
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
