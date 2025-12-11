<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace() ?>;

<?= $class_data->getUseStatements(); ?>

<?= $class_data->getClassDeclaration() ?>
{
    public function __construct(
        private readonly <?= $repository_class ?> $repository,
        private readonly RouterInterface $router,
        RequestStack $requestStack,
        PaginatorInterface $paginator,
        ParameterBagInterface $params
    ) {
        parent::__construct($requestStack, $paginator, $params);
    }

    public function initialize(QueryBuilder $queryBuilder = null, FormInterface $filtersForm = null, Request $request = null): GridBuilder
    {
        // TODO: déplacer ça dans le parent ?
        $request ??= $this->requestStack->getMainRequest();
        // TODO: déplacer ça dans le parent ?
        $filtersForm?->handleRequest($request);

        $queryBuilder ??= $this->repository->createQueryBuilder('<?= $query_entity_alias ?>')
            ->orderBy('<?= $query_entity_alias ?>.id', 'ASC')
        ;

        return parent::initialize($queryBuilder, $filtersForm, $request)
            ->setItemsPerPage(30)
<?php foreach ($columns as $column): ?>
            ->addColumn(
                new TranslatableMessage('<?= $column['name'] ?>'),
                '<?= $column['value'] ?>',
<?php if ($column['template']): ?>
                template: <?= $column['template'] ?>,
<?php endif; ?>
                sortable: '<?= $query_entity_alias ?>.<?= $column['value'] ?>'
            )
<?php endforeach; ?>
            //->addColumn(
            //    new TranslatableMessage('Total'),
            //    fn(<?= $entity_short_name ?> $<?= $entity_var ?>) => $<?= $entity_var ?>->getTotal(),
            //)
            //->addColumn(
            //    'Relation',
            //    'relation',
            //    template: Template::ENTITY,
            //    templateParameters: ['route' => 'app_relation_show'],
            //    sortable: 'u.relation.name'
            //)
            ->addColumn(
                new TranslatableMessage('Actions'),
                fn(<?= $entity_short_name ?> $<?= $entity_var ?>) => [
                    [
                        'name' => new TranslatableMessage('Edit'),
                        'url' => $this->router->generate(
                            'app_<?= strtolower($entity_var) ?>_edit',
                            ['id' => $<?= $entity_snake_case ?>->getId()]
                        ),
                        'btn_type' => 'outline-primary',
                        'icon_class' => 'bi bi-pencil',
                        'modal' => true,
                    ],
                ],
                Template::ACTIONS
            )
            ->addFilter(
                'search',
                fn(QueryBuilder $qb, ?string $formValue) => $qb
                    ->andWhere(
                        $qb->expr()->orX(
<?php foreach ($entity_display_fields as $field): ?>
<?php if ($field['type'] === 'string'): ?>
                            $qb->expr()->like('LOWER(<?= $query_entity_alias ?>.<?= $field['fieldName'] ?>)', $qb->expr()->literal(strtolower("%$formValue%"))),
<?php endif; ?>
<?php endforeach; ?>
                        )
                    )
            )
        ;
    }
}
