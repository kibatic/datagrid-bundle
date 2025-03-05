Kibatic Datagrid Bundle
=======================

Datagrid bundle for Symfony with the following design philosophy : less magic for more flexibility.

It's not the usual one line datagrid generator, it's a more verbose one but we think it's worth it.

Features
--------

- Your entities in a table
- Pagination
- Sortable
- Filterable
- Actions (single & batch)
- Customizable templates
- Only supports Doctrine ORM
- Theme support (Bootstrap 5 included)


Quick start
-----------

### Install the bundle

```bash
composer require kibatic/datagrid-bundle
```

Add this to your `assets/controllers.json` :

```json
{
    "controllers": {
        "@kibatic/datagrid-bundle": {
            "checker": {
                "enabled": true,
                "fetch": "eager"
            }
        }
}
```

You'll most likely also need to enable this twig function : https://twig.symfony.com/doc/2.x/functions/template_from_string.html

### Basic usage

You can simply generate a specialized datagrid builder class skeleton using the make command :

```
bin/console make:datagrid
```

Or do everything manually, for example in your controller :

```php
<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Kibatic\DatagridBundle\Grid\GridBuilder;
use Kibatic\DatagridBundle\Grid\Template;
use Kibatic\DatagridBundle\Grid\Theme;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_project_index', methods: ['GET'])]
    public function index(
        ProjectRepository $projectRepository,
        GridBuilder $gridBuilder,
    ): Response {
        // get current user
        $user = $this->getUser();
        
        // create query builder filtered by current user
        $queryBuilder = $projectRepository->createQueryBuilder('p')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC');
        ;
        $grid = $gridBuilder
            ->initialize(queryBuilder: $queryBuilder)
            ->addColumn('Name', 'name')
            ->addColumn(
                'Created at',
                'createdAt',
                Template::DATETIME,
                sortable: 'createdAt'
            )
            ->getGrid()
        ;


        return $this->render('project/index.html.twig', [
            'grid' => $grid
        ]);
    }
}
```

Then with Symfony UX handy twig components :

```twig
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Project list</h1>

    <twig:datagrid :grid="grid" />
{% endblock %}
```


Or a more classic twig approach :

```twig
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Project list</h1>

    {% include grid.theme ~ '/datagrid.html.twig' %}
{% endblock %}
```


Documentation
-------------

You can find a more advanced example on [how to generate your datagrid](docs/advanced-example.md).

If you want to customize the pagination, use the knp paginator configuration.

```
# config/packages/knp_paginator.yaml
knp_paginator:
    page_limit: 20   
```

If you're using a datagrid inside a live component (symfony ux), [you'll need to do this](docs/advanced-example.md).

Requirements
------------

- Symfony 6
- PHP 8.2
- Doctrine ORM

Roadmap
-------

- Adding a Flex recipe
- Remove Bootstrap 4 and Sonata theme
- More generic column types and template options
