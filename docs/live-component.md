# Live component

If you want to insert a datagrid in a live component template, you'll need to explicitly specify the current route and its parameters for the sort and pagination links to work.

```php
#[AsLiveComponent(name: 'hello')]
final class HelloComponent extends AbstractComponent
{
    // ...

    #[LiveProp]
    public ?string $routeName = null;
    #[LiveProp]
    public ?array $routeParams = [];

    // ...

    public function getGrid(): Grid
    {
        // ...

        return $this->gridBuilder->initialize($this->requestStack->getMainRequest(), $qb);
            // ...
            ->setExplicitRoute($this->routeName, $this->routeParams)
            ->getGrid()
        ;
    }
```

```twig
{{ component('hello', {
    routeName: app.request.attributes.get('_route'),
    routeParams: app.request.attributes.get('_route_params'),
}) }}
```

```twig
<div{{ attributes }}>
    {% include grid.theme ~ '/datagrid.html.twig' %}
</div>
```