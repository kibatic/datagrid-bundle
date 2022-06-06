<?php

namespace Kibatic\DatagridBundle\Grid;

class Filter
{
    public string $formFieldName;
    public $callback;

    public function __construct(
        string $formFieldName,
        callable $callback
    ) {
        $this->formFieldName = $formFieldName;
        $this->callback = $callback;
    }
}
