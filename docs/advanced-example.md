# Advanced example

```php
    // App\Controller\BookController
    /**
     * @Route("/book/list", methods={"GET", "POST"}, name="book_list")
     */
    public function list(
        Request $request,
        GridBuilder $gridBuilder,
        BookRepository $repository
    ): Response {
        $form = $this->createForm(BookFiltersType::class)
            ->handleRequest($request);

        $qb = $repository->createQueryBuilder('b')
            ->leftJoin('b.tags', 't')
            ->addSelect('count(t) as tagsCount')
            ->where('b.published = true')
        ;

        $grid = $gridBuilder
            ->initialize($request, $qb, $form)
            ->setTheme(Theme::BOOTSTRAP4_SONATA) // default theme is Bootstrap 5
            ->addColumn(
                'ID',
                'id', // first way of getting the value, using a string accessor
                templateParameters: ['col_class' => 'col-md-1'],
                sortable: 't.id'
            )
            ->addColumn(
                'Title',
                fn(Book $book) => $book->getTitle(), // second way using a callable returning wanted value
                templateParameters: ['truncate' => 30]
            )
            ->addColumn(
                'Created at',
                fn(Book $book) => $book->getCreatedAt(),
                Template::DATETIME,
                ['format' => 'd/m/Y']
            )
            ->addColumn(
                'Promoted',
                fn(Book $book) => $book->isPromoted(),
                Template::BOOLEAN,
                sortable: 'b.promoted'
            )
            ->addColumn(
                'Editor',
                fn(Book $book) => $book->getEditor()->getName(),
                sortable: 'editor', // The name of the sort option in the query can be customized
                sortableQuery: 'b.editor.name', // and then you can specify what will actually be used in the query to sort
            )
            ->addColumn(
                'Spell checked At',
                fn(Book $book) => $book->getSpellCheckedAt(),
                Template::DATETIME,
                sortable: 'spellCheckedAt',
                sortableQuery: function (QueryBuilder $qb, string $direction)  { // You can also work with the QueryBuilder directly
                    // ORDER BY NULLS LAST does not exist in vanilla doctrine
                    $qb->addSelect('CASE WHEN t.spellCheckedAt IS NULL THEN 1 ELSE 0 END as HIDDEN spellCheckedAtIsNull');
                    $qb->addOrderBy( "spellCheckedAtIsNull", $direction === 'ASC' ? 'DESC': 'ASC');
                    $qb->addOrderBy( "t.spellCheckedAt", $direction);
                }
            )
            ->addColumn(
                'Tags',
                // You can access extra select data too
                value: 'tagsCount', 
                // If you want to use a callback, read the value from the second argument to get any extra select data
                value: fn(Book $book, array $extra) => $extra['tagsCount'],
                sortable: 'tagsCount'
            )
            ->addColumn(
                'Actions',
                fn(Book $book) => [
                    [
                        'name' => 'Edit',
                        'url' => $this->generateUrl('book_edit', ['id' => $book->getId()]),
                        'icon_class' => 'fa fa-pencil'
                    ],
                    [
                        'url' => $this->generateUrl('book_delete', ['id' => $book->getId()]),
                        'name' => 'Delete',
                        'btn_type' => 'danger',
                        'icon_class' => 'fa fa-trash',
                    ],
                    [
                        'url' => $this->generateUrl('book_promote', ['id' => $book->getId()]),
                        'name' => 'Promote',
                        'btn_type' => 'default',
                        'icon_class' => 'fa fa-map-pin',
                        'visible' => !$book->isPromoted(),
                    ],
                    [
                        'url' => $this->generateUrl('book_demote', ['id' => $book->getId()]),
                        'name' => 'Demote',
                        'btn_type' => 'default',
                        'icon_class' => 'fa fa-map-pin',
                        'visible' => $book->isPromoted(),
                    ],
                ],
                Template::ACTIONS,
            )
            ->addBatchAction(
                'delete',
                'Delete',
                $this->generateUrl('book_batch_delete')
            )
            ->addFilter(
                'id',
                fn (QueryBuilder $qb, $formValue) =>
                    $qb->andWhere('t.id = :id')->setParameter('id', $formValue)
            )
            ->addFilter(
                'message',
                fn (QueryBuilder $qb, $formValue) =>
                    $qb->andWhere($qb->expr()->like('LOWER(t.message)', $qb->expr()->literal(strtolower("%$formValue%")=)))
            )
            ->addFilter(
                'promoted',
                fn (QueryBuilder $qb, $formValue) =>
                    $qb->andWhere('t.promoted = :promoted')->setParameter('promoted', $formValue)
            )
            ->getGrid()
        ;

        return $this->render('book/list.html.twig', [
            'grid' => $grid,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/book/batch-delete", methods={"POST"}, name="book_batch_delete")
     */
    public function batchDelete(Request $request): Response
    {
        $request->get('ids');
        // etc.
    }
```

```php
    // App\Form\BookFiltersType
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('id', NumberType::class, ['required' => false])
            ->add('message', TextType::class, ['required' => false])
            ->add('promoted', BooleanChoiceType::class, ['label' => 'Promoted ?'])
        ;
    }
```

```twig
{# templates/book/list.html.twig #}

{% include grid.theme ~ '/datagrid.html.twig' %}
```
