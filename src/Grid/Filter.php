<?php

namespace Kibatic\DatagridBundle\Grid;

class Filter
{
    public string $formFieldName;
    public $callback;
    public bool $enabled;
    public ?string $group;
    public bool $hidden;

    public function __construct(
        string $formFieldName,
        callable $callback,
        bool $enabled = true,
        ?string $group = null,
        bool $hidden = false,
    ) {
        $this->formFieldName = $formFieldName;
        $this->callback = $callback;
        $this->enabled = $enabled;
        $this->group = $group;
        $this->hidden = $hidden;
    }
}
