<?php

namespace Kibatic\DatagridBundle\Grid;

class Filter
{
    public string $formFieldName;
    public $callback;
    public bool $enabled;

    public function __construct(
        string $formFieldName,
        callable $callback,
        bool $enabled = true
    ) {
        $this->formFieldName = $formFieldName;
        $this->callback = $callback;
        $this->enabled = $enabled;
    }
}
