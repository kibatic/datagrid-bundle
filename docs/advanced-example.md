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

        $grid = $gridBuilder
            ->create(
                $repository->createQueryBuilder('b')->where('b.published = true'),
                $request,
                $form
            )
            ->addColumn(
                'ID',
                fn(Thread $thread) => $thread->getId(),
                null,
                ['colClass' => 'col-md-1'],
                't.id'
            )
            ->addColumn(
                'Message',
                fn(Thread $thread) => $thread->getMessage(),
                null,
                ['truncate' => 30]
            )
            ->addColumn(
                'Created at',
                fn(Thread $thread) => $thread->getCreatedAt(),
                Template::DATETIME,
                ['format' => 'd/m/Y']
            )
            ->addColumn(
                'Promoted',
                fn(Thread $thread) => $thread->isPromoted(),
                Template::BOOLEAN,
                [],
                't.promoted'
            )
            ->addColumn(
                'Actions',
                fn(Thread $thread) => [
                    [
                        'name' => 'Edit',
                        'url' => $this->generateUrl('book_edit', ['id' => $thread->getId()]),
                        'iconClass' => 'fa fa-pencil'
                    ],
                    [
                        'url' => $this->generateUrl('book_delete', ['id' => $thread->getId()]),
                        'name' => 'Delete',
                        'btnType' => 'danger',
                        'iconClass' => 'fa fa-trash',
                    ],
                    [
                        'url' => $this->generateUrl('book_promote', ['id' => $thread->getId()]),
                        'name' => 'Promote',
                        'btnType' => 'default',
                        'iconClass' => 'fa fa-map-pin',
                        'visible' => !$book->isPromoted(),
                    ],
                    [
                        'url' => $this->generateUrl('book_demote', ['id' => $thread->getId()]),
                        'name' => 'Demote',
                        'btnType' => 'default',
                        'iconClass' => 'fa fa-map-pin',
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
                    $qb->andWhere($qb->expr()->like('t.message', $qb->expr()->literal("%$formValue%")))
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
        // etc...
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