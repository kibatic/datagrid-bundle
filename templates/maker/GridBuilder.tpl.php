<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace() ?>;

<?= $class_data->getUseStatements(); ?>
use function Symfony\Component\Translation\t;

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
        $queryBuilder ??= $this->repository->createQueryBuilder('<?= $query_entity_alias ?>')
            ->orderBy('<?= $query_entity_alias ?>.id', 'ASC')
        ;

        return parent::initialize($queryBuilder, $filtersForm, $request)
            ->setItemsPerPage(30)
<?php foreach ($columns as $column): ?>
            ->addColumn(
                t('<?= $column['name'] ?>'),
                '<?= $column['value'] ?>',
<?php if ($column['template']): ?>
                template: <?= $column['template'] ?>,
<?php endif; ?>
                sortable: '<?= $query_entity_alias ?>.<?= $column['value'] ?>'
            )
<?php endforeach; ?>
            //->addColumn(
            //    t('Total'),
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
                t('Actions'),
                fn(<?= $entity_short_name ?> $<?= $entity_var ?>) => [
                    [
                        'name' => t('Show'),
                        'url' => $this->router->generate(
                            'app_<?= strtolower($entity_var) ?>_show',
                            ['id' => $<?= $entity_var ?>->getId()]
                        ),
                        'btn_type' => 'outline-primary',
                        'icon' => 'bi:eye',
                        'modal' => true,
                    ],
                    [
                        'name' => t('Edit'),
                        'url' => $this->router->generate(
                            'app_<?= strtolower($entity_var) ?>_edit',
                            ['id' => $<?= $entity_var ?>->getId()]
                        ),
                        'btn_type' => 'outline-primary',
                        'icon' => 'bi:pencil',
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
