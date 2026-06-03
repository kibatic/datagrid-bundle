<?php

namespace Kibatic\DatagridBundle\Grid;

class Filter
{
    public string $formFieldName;
    public $callback;
    public bool $enabled;
    public ?string $group;

    public function __construct(
        string $formFieldName,
        callable $callback,
        bool $enabled = true,
        ?string $group = null
    ) {
        $this->formFieldName = $formFieldName;
        $this->callback = $callback;
        $this->enabled = $enabled;
        $this->group = $group;
    }
}
