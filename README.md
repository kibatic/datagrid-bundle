Kibatic Datagrid Bundle
=======================

Datagrid bundle for Symfony with the following design philosophy : less magic for more flexibility.

It's not the usual one line datagrid generator, it's a more verbose but we think it's worth it.

Features
--------

- Your entities in a table
- Pagination
- Sortable
- Filterable
- Actions (simple & batch)
- Customizable templates
- Only supports Doctrine ORM
- Theme (bootstrap 4)


Quick start
-----------

### Install the bundle

```bash
composer require kibatic/datagrid-bundle
```

### Basic usage

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
        Request $request,
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
            ->create($queryBuilder, $request)
            ->setTheme(Theme::BOOTSTRAP5)
            ->addColumn('Name', 'name')
            ->addColumn(
                'Created at',
                'createdAt',
                Template::DATETIME
            )
            ->getGrid()
        ;


        return $this->render('project/index.html.twig', [
            'grid' => $grid
        ]);
    }
}
```

And the associated twig

```twig
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Project list</h1>

    {% include grid.theme ~ '/datagrid.html.twig' %}
{% endblock %}
```


Documentation
-------------

More information on [how to generate your datagrid](docs/advanced-example.md).

If you want to customize the pagination, use the knp paginator configuration.

```
# config/packages/knp_paginator.yaml
knp_paginator:
    page_limit: 20   
```

Requirements
------------

- Symfony 4.4 or more
- PHP 7.4 or more
- Doctrine ORM

Roadmap
-------

- Adding a Flex recipe
- Upgrading to PHP 8
- Adding Bootstrap 5 theme
- Use Symfony UX
- More column types and template options ?
