<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace() ?>;

<?= $class_data->getUseStatements(); ?>

<?= $class_data->getClassDeclaration() ?>
{
    public function __construct(
        private readonly GridBuilder $gridBuilder,
        private readonly <?= $repository_class ?> $repository,
        private readonly RouterInterface $router,
    ) {
    }

    // TODO: inject master request if null ?
    public function initialize(Request $request, ?FormInterface $form = null): GridBuilder
    {
        $qb = $this->repository->createQueryBuilder('<?= $query_entity_alias ?>')
            ->orderBy('<?= $query_entity_alias ?>.id', 'ASC')
        ;

        $this->gridBuilder->initialize($request, $qb, $form)
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
            //    fn(<?= $entity_short_name ?> <?= $entity_var ?>) => <?= $entity_var ?>->getTotal(),
            //)
            ->addColumn(
                new TranslatableMessage('Actions'),
                fn(<?= $entity_short_name ?> $<?= $entity_var ?>) => [
                    [
                        'name' => new TranslatableMessage('Edit'),
                        'url' => $this->router->generate(
                            'app_<?= $entity_snake_case ?>_edit',
                            ['id' => $<?= $entity_var ?>->getId()]
                        ),
                        'btn_type' => 'outline-primary',
                        'icon_class' => 'bi bi-pencil'
                    ],
                ],
                Template::ACTIONS
            )
            //->addFilter(
            //    'title',
            //    fn(QueryBuilder $qb, ?string $formValue) => $qb->andWhere(
            //        $qb->expr()->like('LOWER(t.title)', $qb->expr()->literal(strtolower("%$formValue%")))
            //    )
            //)
        ;

        return $this->gridBuilder;
    }
}
