<?php

namespace Kibatic\DatagridBundle\Grid;

class Filter
{
    public string $formFieldName;
    /**
     * @var \Closure Applique le filtre au QueryBuilder à partir de la valeur du formulaire.
     */
    public \Closure $callback;
    public bool $enabled;
    public ?string $group;
    public bool $hidden;

    public function __construct(
        string $formFieldName,
        \Closure $callback,
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
